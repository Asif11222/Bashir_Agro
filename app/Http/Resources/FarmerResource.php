<?php

namespace App\Http\Resources;

use App\Models\Farmer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Farmer
 */
class FarmerResource extends JsonResource
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
            'farmer_code' => $this->farmer_code,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'nid' => $this->nid,
            'gender' => $this->gender,
            'address' => $this->address,
            'primary_activity' => $this->primary_activity,
            'experience_years' => $this->experience_years,
            'profile_photo_url' => $this->profile_photo_path ? asset('storage/'.$this->profile_photo_path) : null,
            'app_access_enabled' => (bool) $this->app_access_enabled,
            'status' => $this->status,
            'emergency_contact' => $this->whenLoaded('emergencyContact', function () {
                return [
                    'name' => $this->emergencyContact?->name,
                    'phone' => $this->emergencyContact?->phone,
                    'relationship' => $this->emergencyContact?->relationship,
                ];
            }),
            'locations' => LocationResource::collection($this->whenLoaded('farmLocations')),
        ];
    }
}
