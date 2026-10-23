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
        $totalTasks = (int) ($this->total_tasks ?? 0);
        $completedTasks = (int) ($this->completed_tasks ?? 0);
        $activeTasks = (int) ($this->active_tasks ?? 0);
        $locationsCount = (int) ($this->locations_count ?? ($this->relationLoaded('farmLocations') ? $this->farmLocations->count() : 0));

        $isAssigned = ($activeTasks > 0) || ($totalTasks > 0) || ($locationsCount > 0);
        $progressPercent = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : ($completedTasks > 0 ? 100 : 0);
        $landArea = (float) ($this->relationLoaded('farmLocations') ? $this->farmLocations->sum('area') : 4.0);
        $primaryLocation = $this->relationLoaded('farmLocations') ? $this->farmLocations->first() : null;

        return [
            'id' => $this->id,
            'farmer_code' => $this->farmer_code,
            'full_name' => $this->full_name,
            'name' => $this->full_name,
            'phone' => $this->phone,
            'nid' => $this->nid,
            'gender' => $this->gender,
            'address' => $this->address,
            'region' => $primaryLocation?->name ?? ($this->address ?: 'বাংলাদেশ'),
            'primary_activity' => $this->primary_activity,
            'farm_type' => $this->primary_activity ?: 'কৃষি চাষ',
            'experience_years' => $this->experience_years,
            'profile_photo_url' => $this->profile_photo_path ? asset('storage/'.$this->profile_photo_path) : null,
            'app_access_enabled' => (bool) $this->app_access_enabled,
            'status' => $this->status,
            'is_assigned' => $isAssigned,
            'progress_percent' => $progressPercent,
            'land_area' => $landArea > 0 ? $landArea : 4.0,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'active_tasks' => $activeTasks,
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
