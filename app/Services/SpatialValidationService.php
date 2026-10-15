<?php

namespace App\Services;

use App\Models\FarmLocation;
use Illuminate\Support\Facades\DB;

class SpatialValidationService
{
    /**
     * WGS84 Earth radius in meters.
     */
    public const EARTH_RADIUS_METERS = 6371000;

    /**
     * Determine if PostGIS is enabled and functional in the active database connection.
     */
    public function isPostGisAvailable(): bool
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return false;
        }

        try {
            $result = DB::selectOne("SELECT 1 FROM pg_extension WHERE extname = 'postgis'");

            return ! empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Validate whether a farmer GPS submission falls inside a Farm Location boundary.
     *
     * @return array{
     *     is_within: bool,
     *     distance_meters: float,
     *     radius_meters?: int,
     *     method: string,
     *     polygon_validated: bool
     * }
     */
    public function validatePointAgainstLocation(
        float $lat,
        float $lng,
        FarmLocation $location,
        float $accuracy = 0.0
    ): array {
        // If farm location has a verified polygon boundary, validate against polygon
        if ($location->hasBoundaryPolygon()) {
            return $this->validatePointAgainstPolygon($lat, $lng, $location, $accuracy);
        }

        // Fallback: validate against center latitude/longitude radius
        return $this->validatePointAgainstRadius($lat, $lng, $location);
    }

    /**
     * Validate GPS point against polygon boundary (PostGIS with Ray-Casting fallback).
     *
     * @return array{
     *     is_within: bool,
     *     distance_meters: float,
     *     method: string,
     *     polygon_validated: bool
     * }
     */
    public function validatePointAgainstPolygon(
        float $lat,
        float $lng,
        FarmLocation $location,
        float $accuracy = 0.0
    ): array {
        // Try native PostGIS query if enabled
        if ($this->isPostGisAvailable() && $location->boundary) {
            try {
                $postGisCheck = DB::selectOne(
                    'SELECT 
                        ST_Contains(boundary, ST_SetSRID(ST_MakePoint(?, ?), 4326)) as is_inside,
                        ST_Distance(boundary::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) as distance
                    FROM farm_locations 
                    WHERE id = ?',
                    [$lng, $lat, $lng, $lat, $location->id]
                );

                if ($postGisCheck !== null) {
                    $isInside = (bool) $postGisCheck->is_inside;
                    $distance = (float) ($postGisCheck->distance ?? 0.0);

                    // Accept if strictly inside or within GPS accuracy tolerance (max 10m buffer)
                    $tolerance = min(max($accuracy, 0.0), 10.0);
                    $isWithin = $isInside || ($distance <= $tolerance);

                    return [
                        'is_within' => $isWithin,
                        'distance_meters' => round($distance, 2),
                        'method' => 'polygon_postgis',
                        'polygon_validated' => true,
                    ];
                }
            } catch (\Throwable $e) {
                // Proceed to ray-casting fallback
            }
        }

        // Mathematical Ray-Casting algorithm fallback
        $coords = $location->getPolygonCoordinates();
        if (empty($coords)) {
            return $this->validatePointAgainstRadius($lat, $lng, $location);
        }

        $isInside = $this->isPointInPolygon($lat, $lng, $coords);
        $distance = $isInside ? 0.0 : $this->distanceToPolygonPerimeter($lat, $lng, $coords);

        $tolerance = min(max($accuracy, 0.0), 10.0);
        $isWithin = $isInside || ($distance <= $tolerance);

        return [
            'is_within' => $isWithin,
            'distance_meters' => round($distance, 2),
            'method' => 'polygon_raycast',
            'polygon_validated' => true,
        ];
    }

    /**
     * Fallback validation for locations configured only with a center point and radius.
     *
     * @return array{
     *     is_within: bool,
     *     distance_meters: float,
     *     radius_meters: int,
     *     method: string,
     *     polygon_validated: bool
     * }
     */
    public function validatePointAgainstRadius(float $lat, float $lng, FarmLocation $location): array
    {
        $targetLat = (float) $location->latitude;
        $targetLon = (float) $location->longitude;
        $radius = (int) ($location->geofence_radius_meters ?: 20);

        if (empty($targetLat) || empty($targetLon)) {
            return [
                'is_within' => true,
                'distance_meters' => 0.0,
                'radius_meters' => $radius,
                'method' => 'point_radius',
                'polygon_validated' => false,
            ];
        }

        $distance = $this->haversineDistance($lat, $lng, $targetLat, $targetLon);

        return [
            'is_within' => $distance <= $radius,
            'distance_meters' => round($distance, 2),
            'radius_meters' => $radius,
            'method' => 'point_radius',
            'polygon_validated' => false,
        ];
    }

    /**
     * Test whether point (lat, lng) is strictly inside a closed polygon coordinate ring.
     * Implements Jordan curve theorem (Ray-Casting Algorithm).
     *
     * @param  array<int, array{0: float, 1: float}>  $coordinates  Array of [longitude, latitude]
     */
    public function isPointInPolygon(float $lat, float $lng, array $coordinates): bool
    {
        $inside = false;
        $numPoints = count($coordinates);

        if ($numPoints < 3) {
            return false;
        }

        $j = $numPoints - 1;
        for ($i = 0; $i < $numPoints; $i++) {
            $xi = (float) $coordinates[$i][0]; // longitude
            $yi = (float) $coordinates[$i][1]; // latitude
            $xj = (float) $coordinates[$j][0];
            $yj = (float) $coordinates[$j][1];

            $intersect = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = ! $inside;
            }
            $j = $i;
        }

        return $inside;
    }

    /**
     * Compute shortest distance in meters from point (lat, lng) to the polygon boundary perimeter.
     *
     * @param  array<int, array{0: float, 1: float}>  $coordinates
     */
    public function distanceToPolygonPerimeter(float $lat, float $lng, array $coordinates): float
    {
        $minDistance = PHP_FLOAT_MAX;
        $numPoints = count($coordinates);

        for ($i = 0; $i < $numPoints - 1; $i++) {
            $p1Lon = (float) $coordinates[$i][0];
            $p1Lat = (float) $coordinates[$i][1];
            $p2Lon = (float) $coordinates[$i + 1][0];
            $p2Lat = (float) $coordinates[$i + 1][1];

            $dist = $this->pointToSegmentDistance($lat, $lng, $p1Lat, $p1Lon, $p2Lat, $p2Lon);
            if ($dist < $minDistance) {
                $minDistance = $dist;
            }
        }

        return $minDistance === PHP_FLOAT_MAX ? 0.0 : $minDistance;
    }

    /**
     * Minimum distance from point P to line segment AB in meters.
     */
    public function pointToSegmentDistance(
        float $pLat,
        float $pLon,
        float $aLat,
        float $aLon,
        float $bLat,
        float $bLon
    ): float {
        // Flat projection for local distance approximation
        $midLatRad = deg2rad(($aLat + $bLat) / 2);
        $x = deg2rad($pLon - $aLon) * cos($midLatRad);
        $y = deg2rad($pLat - $aLat);

        $dx = deg2rad($bLon - $aLon) * cos($midLatRad);
        $dy = deg2rad($bLat - $aLat);

        $segmentLengthSquared = $dx * $dx + $dy * $dy;

        if ($segmentLengthSquared <= 1e-12) {
            return $this->haversineDistance($pLat, $pLon, $aLat, $aLon);
        }

        $t = ($x * $dx + $y * $dy) / $segmentLengthSquared;
        $t = max(0.0, min(1.0, $t));

        $nearestLat = $aLat + $t * ($bLat - $aLat);
        $nearestLon = $aLon + $t * ($bLon - $aLon);

        return $this->haversineDistance($pLat, $pLon, $nearestLat, $nearestLon);
    }

    /**
     * High-precision Haversine distance formula between two WGS84 GPS points.
     */
    public function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return $angle * self::EARTH_RADIUS_METERS;
    }

    /**
     * Calculate spherical geodesic area of polygon in specified unit.
     *
     * @param  array<int, array{0: float, 1: float}>  $coordinates  Array of [longitude, latitude]
     */
    public function calculatePolygonArea(array $coordinates, string $unit = 'acre'): float
    {
        $numPoints = count($coordinates);
        if ($numPoints < 3) {
            return 0.0;
        }

        $totalAreaSqMeters = 0.0;

        for ($i = 0; $i < $numPoints - 1; $i++) {
            $p1 = $coordinates[$i];
            $p2 = $coordinates[$i + 1];

            $lon1 = deg2rad($p1[0]);
            $lat1 = deg2rad($p1[1]);
            $lon2 = deg2rad($p2[0]);
            $lat2 = deg2rad($p2[1]);

            $totalAreaSqMeters += ($lon2 - $lon1) * (2 + sin($lat1) + sin($lat2));
        }

        $totalAreaSqMeters = abs($totalAreaSqMeters * (self::EARTH_RADIUS_METERS * self::EARTH_RADIUS_METERS) / 2.0);

        return match (strtolower($unit)) {
            'acre', 'একর' => round($totalAreaSqMeters * 0.000247105, 2),
            'hectare', 'হেক্টর' => round($totalAreaSqMeters / 10000.0, 2),
            'bigha', 'বিঘা' => round($totalAreaSqMeters * 0.00074749, 2),
            'shatangsho', 'শতাংশ', 'decimal' => round($totalAreaSqMeters * 0.0247105, 2),
            default => round($totalAreaSqMeters, 2),
        };
    }

    /**
     * Compute approximate centroid [latitude, longitude] of polygon ring.
     *
     * @param  array<int, array{0: float, 1: float}>  $coordinates  Array of [longitude, latitude]
     * @return array{latitude: float, longitude: float}
     */
    public function calculateCentroid(array $coordinates): array
    {
        $numPoints = count($coordinates);
        if ($numPoints === 0) {
            return ['latitude' => 0.0, 'longitude' => 0.0];
        }

        $sumLat = 0.0;
        $sumLon = 0.0;
        $count = 0;

        // Ignore redundant closing vertex if present
        $pointsToConsider = ($coordinates[0] === $coordinates[$numPoints - 1] && $numPoints > 1)
            ? $numPoints - 1
            : $numPoints;

        for ($i = 0; $i < $pointsToConsider; $i++) {
            $sumLon += (float) $coordinates[$i][0];
            $sumLat += (float) $coordinates[$i][1];
            $count++;
        }

        return [
            'latitude' => round($sumLat / $count, 7),
            'longitude' => round($sumLon / $count, 7),
        ];
    }
}
