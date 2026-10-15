<?php

namespace App\Http\Resources;

use App\Models\TaskTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaskTemplate
 */
class TaskResource extends JsonResource
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
            'task_code' => $this->task_code,
            'name' => $this->name,
            'category' => $this->category,
            'instructions' => $this->instructions,
            'requires_evidence_photo' => (bool) ($this->requires_evidence_photo ?? ($this->required_photo_count > 0 || $this->require_realtime_photo)),
            'min_photos_required' => (int) ($this->min_photos_required ?? $this->required_photo_count ?? 0),
            'requires_geofence_validation' => (bool) ($this->requires_geofence_validation ?? $this->require_geofence ?? false),
            'status' => $this->status,
        ];
    }
}
