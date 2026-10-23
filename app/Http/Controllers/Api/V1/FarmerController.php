<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FarmerResource;
use App\Models\Farmer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmerController extends Controller
{
    /**
     * Get authenticated farmer's profile or specified farmer.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        $farmer = $user->farmerProfile;
        if (! $farmer && $user->isAdmin()) {
            $farmer = Farmer::first();
        }

        if (! $farmer) {
            return response()->json([
                'success' => false,
                'message' => 'কৃষক প্রোফাইল খুঁজে পাওয়া যায়নি।',
            ], 404);
        }

        $farmer->load(['emergencyContact', 'farmLocations'])->loadCount([
            'taskAssignments as total_tasks',
            'taskAssignments as completed_tasks' => function ($q) {
                $q->where('status', 'completed');
            },
            'taskAssignments as active_tasks' => function ($q) {
                $q->whereIn('status', ['assigned', 'pending', 'in_progress', 'late']);
            },
            'farmLocations as locations_count',
        ]);

        return response()->json([
            'success' => true,
            'data' => new FarmerResource($farmer),
        ]);
    }

    /**
     * Display a listing of farmers for admin and owner consumption.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Farmer::with(['farmLocations', 'emergencyContact'])
            ->withCount([
                'taskAssignments as total_tasks',
                'taskAssignments as completed_tasks' => function ($q) {
                    $q->where('status', 'completed');
                },
                'taskAssignments as active_tasks' => function ($q) {
                    $q->whereIn('status', ['assigned', 'pending', 'in_progress', 'late']);
                },
                'farmLocations as locations_count',
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('farmer_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $farmers = $query->latest('id')->get();

        return response()->json([
            'success' => true,
            'data' => FarmerResource::collection($farmers),
            'count' => $farmers->count(),
        ]);
    }

    /**
     * Display the specified farmer profile.
     */
    public function show(Farmer $farmer): JsonResponse
    {
        $farmer->load(['emergencyContact', 'farmLocations'])->loadCount([
            'taskAssignments as total_tasks',
            'taskAssignments as completed_tasks' => function ($q) {
                $q->where('status', 'completed');
            },
            'taskAssignments as active_tasks' => function ($q) {
                $q->whereIn('status', ['assigned', 'pending', 'in_progress', 'late']);
            },
            'farmLocations as locations_count',
        ]);

        return response()->json([
            'success' => true,
            'data' => new FarmerResource($farmer),
        ]);
    }
}
