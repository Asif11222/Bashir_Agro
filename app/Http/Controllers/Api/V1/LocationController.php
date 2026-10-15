<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\FarmLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Display a listing of active farm locations.
     */
    public function index(Request $request): JsonResponse
    {
        $locations = FarmLocation::where('status', 'active')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => LocationResource::collection($locations),
        ]);
    }

    /**
     * Display the specified farm location.
     */
    public function show(FarmLocation $location): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new LocationResource($location),
        ]);
    }
}
