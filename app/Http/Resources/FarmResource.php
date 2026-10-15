<?php

namespace App\Http\Resources;

use App\Models\FarmLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FarmLocation
 */
class FarmResource extends JsonResource
{
    /**
     * Transform the farm resource into an array for mobile and API consumers.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location_code' => $this->location_code,
            'farmer_id' => $this->farmer_id,
            'name' => $this->name,
            'type' => $this->type,
            'address' => $this->address,
            'boundary' => $this->boundary_geojson,
            'area' => $this->area ? (float) $this->area : null,
            'area_unit' => $this->area_unit ?? 'একর',
            'center' => [
                'latitude' => $this->latitude ? (float) $this->latitude : null,
                'longitude' => $this->longitude ? (float) $this->longitude : null,
            ],
            'geofence_radius_meters' => (int) $this->geofence_radius_meters,
            'status' => $this->status,
            'farmer' => $this->whenLoaded('farmer', function () {
                if (! $this->farmer) {
                    return null;
                }

                return [
                    'id' => $this->farmer->id,
                    'farmer_code' => $this->farmer->farmer_code,
                    'full_name' => $this->farmer->full_name,
                    'phone' => $this->farmer->phone,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
