<?php

namespace App\Http\Resources;

use App\Models\TaskAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaskAssignment
 */
class AssignmentResource extends JsonResource
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
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'formatted_time_range' => $this->formatted_time_range,
            'is_past_deadline' => $this->is_past_deadline,
            'priority' => $this->priority,
            'status' => $this->status,
            'notes' => $this->notes,
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'work_duration' => $this->work_duration,
            'task' => new TaskResource($this->whenLoaded('taskTemplate')),
            'farmer' => new FarmerResource($this->whenLoaded('farmer')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'submission' => new SubmissionResource($this->whenLoaded('submission')),
            'verification_status' => $this->relationLoaded('submission') ? $this->submission?->verification_status : null,
        ];
    }
}
