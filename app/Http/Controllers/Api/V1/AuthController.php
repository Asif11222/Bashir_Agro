<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FarmerResource;
use App\Models\Farmer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate farmer/user and issue a Sanctum personal access token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $loginInput = trim($request->input('login'));
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $normalizedLogin = str_replace($bn, $en, $loginInput);

        /** @var User|null $user */
        if (preg_match('/^F-\d+$/i', $normalizedLogin)) {
            $farmer = Farmer::where('farmer_code', strtoupper($normalizedLogin))->first();
            $user = $farmer?->user;
        } else {
            $loginField = filter_var($normalizedLogin, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
            $user = User::where($loginField, $normalizedLogin)
                ->orWhere($loginField, $loginInput)
                ->first();
        }

        $rawPassword = (string) $request->input('password');
        $normalizedPassword = str_replace($bn, $en, $rawPassword);

        if (! $user || ! $user->is_active || (! Hash::check($rawPassword, $user->password) && ! Hash::check($normalizedPassword, $user->password))) {
            throw ValidationException::withMessages([
                'login' => ['প্রদত্ত তথ্য সঠিক নয় অথবা অ্যাকাউন্টটি নিষ্ক্রিয় রয়েছে।'],
            ]);
        }

        $deviceName = $request->input('device_name', 'farmer-mobile-device');
        $abilities = $user->isFarmer()
            ? ['tasks:read', 'tasks:submit', 'profile:read']
            : ['*'];

        $token = $user->createToken($deviceName, $abilities)->plainTextToken;

        $user->update(['last_login_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'লগইন সফল হয়েছে।',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
                'farmer_profile' => $user->farmerProfile ? new FarmerResource($user->farmerProfile->load('farmLocations')) : null,
            ],
        ]);
    }

    /**
     * Get the authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load('farmerProfile.farmLocations', 'farmerProfile.emergencyContact');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
                'farmer_profile' => $user->farmerProfile ? new FarmerResource($user->farmerProfile) : null,
            ],
        ]);
    }

    /**
     * Revoke current user session token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'সফলভাবে লগআউট হয়েছে।',
        ]);
    }
}
