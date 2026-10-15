<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FarmResource;
use App\Models\ActivityLog;
use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Services\SpatialValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FarmApiController extends Controller
{
    public function __construct(
        protected SpatialValidationService $spatialService
    ) {}

    /**
     * Display a listing of farms.
     * Returns farmer's assigned farms if authenticated as farmer,
     * or all active farms if admin/manager.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = FarmLocation::with('farmer')->latest('id');

        if ($user && $user->isFarmer() && $user->farmerProfile) {
            $farmerId = $user->farmerProfile->id;
            $query->where(function ($q) use ($farmerId) {
                $q->where('farmer_id', $farmerId)
                    ->orWhereHas('farmers', function ($sub) use ($farmerId) {
                        $sub->where('farmers.id', $farmerId);
                    });
            });
        } elseif ($request->filled('farmer_id')) {
            $farmerId = (int) $request->input('farmer_id');
            $query->where(function ($q) use ($farmerId) {
                $q->where('farmer_id', $farmerId)
                    ->orWhereHas('farmers', function ($sub) use ($farmerId) {
                        $sub->where('farmers.id', $farmerId);
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'active');
        }

        $farms = $query->get();

        return response()->json([
            'success' => true,
            'data' => FarmResource::collection($farms),
            'count' => $farms->count(),
        ]);
    }

    /**
     * Display the specified farm boundary and details.
     */
    public function show(Request $request, string|int $id): JsonResponse
    {
        $farm = FarmLocation::with('farmer')->findOrFail($id);
        $user = $request->user();

        // Check ownership if farmer
        if ($user && $user->isFarmer() && $user->farmerProfile) {
            $farmerId = $user->farmerProfile->id;
            $isAssigned = ($farm->farmer_id === $farmerId) || $farm->farmers()->where('farmers.id', $farmerId)->exists();
            if (! $isAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'এই ফার্মের সীমানা দেখার অনুমতি আপনার নেই।',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => new FarmResource($farm),
        ]);
    }

    /**
     * Store a newly created farm boundary.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'boundary' => ['required'],
            'farmer_id' => ['nullable', 'exists:farmers,id'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'area_unit' => ['nullable', 'string', 'max:30'],
            'geofence_radius_meters' => ['nullable', 'integer', 'min:5', 'max:1000'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $user = $request->user();
        if ($user && $user->isFarmer() && $user->farmerProfile && empty($validated['farmer_id'])) {
            $validated['farmer_id'] = $user->farmerProfile->id;
        }

        $geoJson = $this->parseAndValidateGeoJson($validated['boundary']);
        $coords = $geoJson['coordinates'][0];

        // Auto-calculate centroid if not provided
        $centroid = $this->spatialService->calculateCentroid($coords);

        // Auto-calculate area if not provided
        $unit = $validated['area_unit'] ?? 'একর';
        $area = isset($validated['area'])
            ? (float) $validated['area']
            : $this->spatialService->calculatePolygonArea($coords, $unit);

        $farm = DB::transaction(function () use ($validated, $geoJson, $centroid, $area, $unit, $user) {
            $locationCode = FarmLocation::generateNextLocationCode();

            $farm = FarmLocation::create([
                'location_code' => $locationCode,
                'farmer_id' => $validated['farmer_id'] ?? null,
                'name' => $validated['name'],
                'type' => $validated['type'] ?? 'কৃষি জমি',
                'address' => $validated['address'] ?? null,
                'boundary_geojson' => $geoJson,
                'area' => $area,
                'area_unit' => $unit,
                'latitude' => $centroid['latitude'],
                'longitude' => $centroid['longitude'],
                'geofence_radius_meters' => $validated['geofence_radius_meters'] ?? 20,
                'status' => $validated['status'] ?? 'active',
                'created_by' => $user?->id,
            ]);

            // Sync with PostGIS if available
            $this->syncPostGisGeometry($farm, $geoJson);

            // Sync farmer in pivot table for backward compatibility
            if (! empty($validated['farmer_id'])) {
                $farm->farmers()->syncWithoutDetaching([$validated['farmer_id']]);
            }

            ActivityLog::record(
                action: 'farm.boundary_created',
                description: "নতুন ফার্ম সীমানা তৈরি করা হয়েছে: {$farm->name} ({$farm->location_code})",
                subject: $farm,
                newValues: $farm->toArray()
            );

            return $farm;
        });

        $farm->load('farmer');

        return response()->json([
            'success' => true,
            'message' => 'ফার্ম ও সীমানা সফলভাবে তৈরি করা হয়েছে।',
            'data' => new FarmResource($farm),
        ], 201);
    }

    /**
     * Update the specified farm boundary.
     */
    public function update(Request $request, string|int $id): JsonResponse
    {
        $farm = FarmLocation::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'boundary' => ['sometimes', 'required'],
            'farmer_id' => ['sometimes', 'nullable', 'exists:farmers,id'],
            'area' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'area_unit' => ['sometimes', 'nullable', 'string', 'max:30'],
            'geofence_radius_meters' => ['sometimes', 'nullable', 'integer', 'min:5', 'max:1000'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $geoJson = null;
        if (isset($validated['boundary'])) {
            $geoJson = $this->parseAndValidateGeoJson($validated['boundary']);
            $coords = $geoJson['coordinates'][0];

            $centroid = $this->spatialService->calculateCentroid($coords);
            $validated['latitude'] = $centroid['latitude'];
            $validated['longitude'] = $centroid['longitude'];

            if (! isset($validated['area'])) {
                $unit = $validated['area_unit'] ?? $farm->area_unit ?? 'একর';
                $validated['area'] = $this->spatialService->calculatePolygonArea($coords, $unit);
            }

            $validated['boundary_geojson'] = $geoJson;
            unset($validated['boundary']);
        }

        DB::transaction(function () use ($farm, $validated, $geoJson) {
            $oldValues = $farm->toArray();
            $farm->update($validated);

            if ($geoJson !== null) {
                $this->syncPostGisGeometry($farm, $geoJson);
            }

            if (isset($validated['farmer_id'])) {
                if ($validated['farmer_id']) {
                    $farm->farmers()->sync([$validated['farmer_id']]);
                } else {
                    $farm->farmers()->detach();
                }
            }

            ActivityLog::record(
                action: 'farm.boundary_updated',
                description: "ফার্ম সীমানা হালনাগাদ করা হয়েছে: {$farm->name} ({$farm->location_code})",
                subject: $farm,
                oldValues: $oldValues,
                newValues: $farm->toArray()
            );
        });

        $farm->load('farmer');

        return response()->json([
            'success' => true,
            'message' => 'ফার্ম ও সীমানা সফলভাবে হালনাগাদ করা হয়েছে।',
            'data' => new FarmResource($farm),
        ]);
    }

    /**
     * Parse and strictly validate GeoJSON Polygon input.
     *
     * @return array{type: 'Polygon', coordinates: array<int, array<int, array{0: float, 1: float}>>}
     */
    protected function parseAndValidateGeoJson(mixed $boundary): array
    {
        if (is_string($boundary)) {
            $decoded = json_decode($boundary, true);
            if (! is_array($decoded)) {
                throw ValidationException::withMessages([
                    'boundary' => 'সীমানা অবশ্যই সঠিক GeoJSON ফরম্যাটে হতে হবে।',
                ]);
            }
            $boundary = $decoded;
        }

        // If wrapped in Feature, extract geometry
        if (isset($boundary['type']) && strtolower($boundary['type']) === 'feature' && isset($boundary['geometry'])) {
            $boundary = $boundary['geometry'];
        }

        if (
            ! is_array($boundary)
            || ! isset($boundary['type'])
            || strtolower($boundary['type']) !== 'polygon'
            || empty($boundary['coordinates'])
            || ! is_array($boundary['coordinates'][0])
        ) {
            throw ValidationException::withMessages([
                'boundary' => 'সীমানা অবশ্যই GeoJSON Polygon হতে হবে।',
            ]);
        }

        $ring = $boundary['coordinates'][0];
        if (count($ring) < 4) {
            throw ValidationException::withMessages([
                'boundary' => 'বহুভুজ সীমানার জন্য কমপক্ষে ৪টি স্থানাঙ্ক বিন্দু প্রয়োজন।',
            ]);
        }

        // Ensure polygon ring is closed (first coord matches last coord)
        $first = $ring[0];
        $last = $ring[count($ring) - 1];
        if (abs($first[0] - $last[0]) > 1e-7 || abs($first[1] - $last[1]) > 1e-7) {
            $ring[] = $first;
        }

        return [
            'type' => 'Polygon',
            'coordinates' => [$ring],
        ];
    }

    /**
     * Sync PostGIS geometry column if PostGIS extension is available.
     */
    protected function syncPostGisGeometry(FarmLocation $farm, array $geoJson): void
    {
        if ($this->spatialService->isPostGisAvailable()) {
            try {
                $geoJsonString = json_encode($geoJson);
                DB::statement(
                    'UPDATE farm_locations SET boundary = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326) WHERE id = ?',
                    [$geoJsonString, $farm->id]
                );
            } catch (\Throwable $e) {
                // Ignore PostGIS statement errors
            }
        }
    }
}
