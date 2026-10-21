<?php

namespace App\Http\Resources;

use App\Models\FarmLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FarmLocation
 */
class LocationResource extends JsonResource
{
    /**
     * Transform the resource into an array for mobile and API consumption.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location_code' => $this->location_code,
            'name' => $this->name,
            'type' => $this->type,
            'address' => $this->address,
            'area' => $this->area ? (float) $this->area : null,
            'area_unit' => $this->area_unit,
            'coordinates' => [
                'latitude' => $this->latitude ? (float) $this->latitude : null,
                'longitude' => $this->longitude ? (float) $this->longitude : null,
            ],
            'farmer_id' => $this->farmer_id,
            'farmers_count' => (int) ($this->farmers_count ?? 0),
            'tasks_count' => (int) ($this->task_assignments_count ?? 0),
            'boundary' => $this->getEffectiveBoundaryGeoJson(),
            'geofence_radius_meters' => (int) $this->geofence_radius_meters,
            'status' => $this->status,
        ];
    }
}
