<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Notifications\AssignmentCompletedNotification;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TaskAssignmentService
{
    /**
     * Create a new task assignment with transaction, activity log, and notifications.
     *
     * @param  array<string, mixed>  $data
     */
    public function createAssignment(array $data, ?User $user = null): TaskAssignment
    {
        $userId = $user ? $user->id : auth()->id();

        return DB::transaction(function () use ($data, $userId) {
            $assignment = TaskAssignment::create([
                'task_template_id' => $data['task_template_id'],
                'farmer_id' => $data['farmer_id'],
                'farm_location_id' => $data['farm_location_id'],
                'scheduled_date' => $data['scheduled_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
                'status' => $data['status'] ?? 'assigned',
                'notes' => $data['notes'] ?? null,
                'assigned_by' => $userId,
                'assigned_at' => now(),
            ]);

            $assignment->load(['farmer', 'taskTemplate', 'location']);

            $farmerName = $assignment->farmer->full_name ?? 'কৃষক';
            $taskName = $assignment->taskTemplate->name ?? 'কাজ';
            $locationName = $assignment->location->name ?? 'ফার্ম';

            ActivityLog::record(
                action: 'assignment.created',
                description: "কৃষক {$farmerName}-কে {$taskName} কাজ বরাদ্দ করা হয়েছে ({$locationName})",
                subject: $assignment,
                newValues: $assignment->toArray()
            );

            NotificationService::notifyAdmins(new TaskAssignedNotification($assignment));
            DashboardService::clearKpiCache();

            return $assignment;
        });
    }

    /**
     * Update assignment status with appropriate timestamps, logs, and notifications.
     *
     * @param  array<string, mixed>  $additionalData
     */
    public function updateStatus(TaskAssignment $assignment, string $newStatus, array $additionalData = [], ?User $user = null): TaskAssignment
    {
        $validStatuses = ['assigned', 'in_progress', 'completed', 'cancelled', 'late'];
        if (! in_array($newStatus, $validStatuses, true)) {
            throw new InvalidArgumentException("অকার্যকর কাজের অবস্থা: {$newStatus}");
        }

        $prevStatus = $assignment->status;
        $oldValues = $assignment->toArray();

        return DB::transaction(function () use ($assignment, $newStatus, $prevStatus, $oldValues, $additionalData) {
            $updateData = array_merge($additionalData, [
                'status' => $newStatus,
            ]);

            if ($prevStatus !== 'in_progress' && $newStatus === 'in_progress' && ! $assignment->started_at) {
                $updateData['started_at'] = now();
            }

            if ($prevStatus !== 'completed' && $newStatus === 'completed' && ! $assignment->completed_at) {
                $updateData['completed_at'] = now();
            }

            $assignment->update($updateData);
            $assignment->load(['farmer', 'taskTemplate', 'location']);

            $taskName = $assignment->taskTemplate->name ?? 'কাজ';
            $farmerName = $assignment->farmer->full_name ?? 'কৃষক';

            if ($prevStatus !== $newStatus) {
                $action = 'assignment.status_changed';
                $description = "কাজের বরাদ্দের অবস্থা পরিবর্তন: {$taskName} ({$prevStatus} → {$newStatus})";
            } else {
                $action = 'assignment.updated';
                $description = "কাজের বরাদ্দ তথ্য হালনাগাদ করা হয়েছে: {$taskName} ({$farmerName})";
            }

            ActivityLog::record(
                action: $action,
                description: $description,
                subject: $assignment,
                oldValues: $oldValues,
                newValues: $assignment->toArray()
            );

            if ($prevStatus !== 'completed' && $newStatus === 'completed') {
                NotificationService::notifyAdmins(new AssignmentCompletedNotification($assignment));
            }

            DashboardService::clearKpiCache();

            return $assignment;
        });
    }

    /**
     * Cancel an active or pending assignment.
     */
    public function cancelAssignment(TaskAssignment $assignment, ?string $reason = null, ?User $user = null): TaskAssignment
    {
        $additionalData = [];
        if ($reason) {
            $currentNotes = $assignment->notes ? $assignment->notes."\n" : '';
            $additionalData['notes'] = $currentNotes."বাতিলের কারণ: {$reason}";
        }

        return $this->updateStatus($assignment, 'cancelled', $additionalData, $user);
    }
}
