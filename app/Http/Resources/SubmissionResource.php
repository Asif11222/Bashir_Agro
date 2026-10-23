<?php

namespace App\Http\Resources;

use App\Models\TaskSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaskSubmission
 */
class SubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->task_assignment_id,
            'farmer_id' => $this->farmer_id,
            'notes' => $this->notes ?? $this->farmer_note,
            'coordinates' => [
                'latitude' => $this->latitude ? (float) $this->latitude : null,
                'longitude' => $this->longitude ? (float) $this->longitude : null,
                'accuracy' => $this->accuracy ? (float) $this->accuracy : null,
            ],
            'geofence' => [
                'passed' => $this->geofence_passed === null ? null : (bool) $this->geofence_passed,
                'distance_meters' => $this->distance_meters ? (float) $this->distance_meters : null,
            ],
            'status' => $this->status,
            'verification_status' => $this->verification_status ?? $this->validation_status,
            'is_late' => $this->is_late === null ? null : (bool) $this->is_late,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'photos' => $this->photos ?? [],
            'evidence_photos' => $this->whenLoaded('evidencePhotos', function () {
                return $this->evidencePhotos->map(fn ($p) => [
                    'id' => $p->id,
                    'url' => asset('storage/'.($p->file_path ?? $p->photo_path)),
                    'caption' => $p->caption ?? null,
                ]);
            }),
            'farmer' => new FarmerResource($this->whenLoaded('farmer')),
            'assignment' => new AssignmentResource($this->whenLoaded('taskAssignment')),
        ];
    }
}
