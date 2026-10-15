<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farmer_id',
        'location_code',
        'name',
        'type',
        'address',
        'boundary_geojson',
        'boundary',
        'area',
        'area_unit',
        'latitude',
        'longitude',
        'geofence_radius_meters',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'area' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'geofence_radius_meters' => 'integer',
            'boundary_geojson' => 'array',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class, 'farmer_id');
    }

    public function farmers(): BelongsToMany
    {
        return $this->belongsToMany(Farmer::class, 'farmer_location')->withTimestamps();
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine if this location has an active polygon boundary.
     */
    public function hasBoundaryPolygon(): bool
    {
        if (empty($this->boundary_geojson)) {
            return false;
        }

        $geojson = is_string($this->boundary_geojson)
            ? json_decode($this->boundary_geojson, true)
            : $this->boundary_geojson;

        return is_array($geojson)
            && isset($geojson['type'])
            && strtolower($geojson['type']) === 'polygon'
            && ! empty($geojson['coordinates'][0]);
    }

    /**
     * Retrieve the polygon coordinate ring: array of [longitude, latitude].
     *
     * @return array<int, array{0: float, 1: float}>|null
     */
    public function getPolygonCoordinates(): ?array
    {
        if (! $this->hasBoundaryPolygon()) {
            return null;
        }

        $geojson = is_string($this->boundary_geojson)
            ? json_decode($this->boundary_geojson, true)
            : $this->boundary_geojson;

        return $geojson['coordinates'][0] ?? null;
    }

    /**
     * Generate an authoritative boundary polygon GeoJSON array around this location centroid.
     *
     * @return array<string, mixed>
     */
    public function generateDefaultBoundaryGeoJson(int $pointsCount = 6): array
    {
        $lat = (float) $this->latitude;
        $lng = (float) $this->longitude;
        $radiusMeters = $this->geofence_radius_meters > 0 ? (float) $this->geofence_radius_meters : 30.0;

        $coords = [];
        $dLatPerMeter = 1.0 / 111320.0;
        $dLngPerMeter = 1.0 / (111320.0 * max(0.1, cos(deg2rad($lat))));

        for ($i = 0; $i < $pointsCount; $i++) {
            $angle = 2.0 * M_PI * ($i / $pointsCount);
            $r = $radiusMeters * (0.9 + 0.15 * sin(2 * $angle));
            $ptLat = $lat + ($r * sin($angle) * $dLatPerMeter);
            $ptLng = $lng + ($r * cos($angle) * $dLngPerMeter);
            $coords[] = [round($ptLng, 7), round($ptLat, 7)];
        }
        $coords[] = $coords[0]; // Close polygon ring

        return [
            'type' => 'Polygon',
            'coordinates' => [$coords],
        ];
    }

    /**
     * Get the effective boundary GeoJSON (either stored or computed).
     *
     * @return array<string, mixed>|null
     */
    public function getEffectiveBoundaryGeoJson(): ?array
    {
        if ($this->hasBoundaryPolygon()) {
            return is_string($this->boundary_geojson)
                ? json_decode($this->boundary_geojson, true)
                : $this->boundary_geojson;
        }

        if ($this->latitude && $this->longitude) {
            return $this->generateDefaultBoundaryGeoJson();
        }

        return null;
    }

    /**
     * Generate the next sequential authoritative location code.
     */
    public static function generateNextLocationCode(): string
    {
        $maxNum = 0;
        $allCodes = static::withTrashed()->pluck('location_code');
        foreach ($allCodes as $code) {
            if (preg_match('/LOC-(\d+)/i', $code, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum + 1;
        $newCode = sprintf('LOC-%06d', $nextNum);

        while (static::withTrashed()->where('location_code', $newCode)->exists()) {
            $nextNum++;
            $newCode = sprintf('LOC-%06d', $nextNum);
        }

        return $newCode;
    }
}
