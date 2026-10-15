<?php

namespace App\Services;

use App\Models\FarmLocation;
use Illuminate\Support\Facades\DB;

class GeofenceService
{
    /**
     * Earth radius in meters.
     */
    public const EARTH_RADIUS_METERS = 6371000;

    public function __construct(
        protected ?SpatialValidationService $spatialValidationService = null
    ) {
        $this->spatialValidationService ??= new SpatialValidationService;
    }

    /**
     * Calculate distance in meters between two GPS coordinates using PostGIS (if available)
     * with high-precision Haversine mathematical fallback.
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Try PostGIS ST_Distance if on PostgreSQL and PostGIS is enabled
        if (DB::connection()->getDriverName() === 'pgsql') {
            try {
                $result = DB::selectOne(
                    'SELECT ST_Distance(
                        ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                        ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                    ) as distance',
                    [$lon1, $lat1, $lon2, $lat2]
                );

                if ($result && isset($result->distance)) {
                    return (float) $result->distance;
                }
            } catch (\Throwable $e) {
                // Fallback to Haversine
            }
        }

        return $this->haversineDistance($lat1, $lon1, $lat2, $lon2);
    }

    /**
     * High-precision Haversine distance formula fallback.
     */
    public function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return $this->spatialValidationService->haversineDistance($lat1, $lon1, $lat2, $lon2);
    }

    /**
     * Validate whether the submission coordinates fall within the Farm Location's geofence boundary.
     * Supports both verified polygon boundaries and legacy center-point radius geofences.
     *
     * @return array{distance_meters: float, radius_meters: int, is_within: bool, polygon_validated?: bool}
     */
    public function validate(float $submissionLat, float $submissionLon, FarmLocation $location, float $accuracy = 0.0): array
    {
        if ($location->hasBoundaryPolygon()) {
            $result = $this->spatialValidationService->validatePointAgainstPolygon($submissionLat, $submissionLon, $location, $accuracy);

            return [
                'distance_meters' => $result['distance_meters'],
                'radius_meters' => (int) ($location->geofence_radius_meters ?: 20),
                'is_within' => $result['is_within'],
                'polygon_validated' => true,
            ];
        }

        $targetLat = (float) $location->latitude;
        $targetLon = (float) $location->longitude;
        $radius = (int) ($location->geofence_radius_meters ?: 20);

        // If location does not have coordinates defined, default to passed
        if (empty($targetLat) || empty($targetLon)) {
            return [
                'distance_meters' => 0.0,
                'radius_meters' => $radius,
                'is_within' => true,
                'polygon_validated' => false,
            ];
        }

        $distance = $this->calculateDistance($submissionLat, $submissionLon, $targetLat, $targetLon);

        return [
            'distance_meters' => round($distance, 2),
            'radius_meters' => $radius,
            'is_within' => $distance <= $radius,
            'polygon_validated' => false,
        ];
    }
}
