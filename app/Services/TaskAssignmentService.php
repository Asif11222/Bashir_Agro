<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Farmer;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Notifications\AssignmentCompletedNotification;
use App\Notifications\TaskAssignedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TaskAssignmentService
{
    /**
     * Create a new task assignment (or recurring series) with transaction, activity log, and notifications.
     *
     * @param  array<string, mixed>  $data
     */
    public function createAssignment(array $data, ?User $user = null): TaskAssignment
    {
        $recurrenceType = $data['recurrence_type'] ?? 'once';

        if (in_array($recurrenceType, ['daily', 'weekly', 'monthly'], true)) {
            return $this->createRecurringAssignments($data, $user);
        }

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
                'is_recurring' => false,
                'recurrence_type' => 'once',
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

            if ($assignment->farmer && $assignment->farmer->user) {
                $assignment->farmer->user->notify(new TaskAssignedNotification($assignment));
            }

            DashboardService::clearKpiCache();

            return $assignment;
        });
    }

    /**
     * Create recurring assignments (Daily, Weekly, Monthly) in batch.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRecurringAssignments(array $data, ?User $user = null): TaskAssignment
    {
        $userId = $user ? $user->id : auth()->id();
        $recurrenceType = $data['recurrence_type'];
        $recurringGroupId = 'REC-'.date('Ymd').'-'.strtoupper(Str::random(6));

        $occurrences = [];

        if (!empty($data['custom_schedule']) && is_array($data['custom_schedule']) && count($data['custom_schedule']) > 0) {
            foreach ($data['custom_schedule'] as $item) {
                if (!empty($item['scheduled_date']) && !empty($item['start_time']) && !empty($item['task_template_id'])) {
                    $occurrences[] = [
                        'scheduled_date' => Carbon::parse($item['scheduled_date'])->toDateString(),
                        'task_template_id' => (int) $item['task_template_id'],
                        'start_time' => $item['start_time'],
                        'end_time' => !empty($item['end_time']) ? $item['end_time'] : null,
                        'priority' => $item['priority'] ?? 'normal',
                        'notes' => $item['notes'] ?? ($data['notes'] ?? null),
                    ];
                }
            }
        } elseif ($recurrenceType === 'daily') {
            $startDate = Carbon::parse($data['start_date'])->startOfDay();
            $endDate = Carbon::parse($data['end_date'])->startOfDay();
            $dailyTasks = $data['daily_tasks'] ?? [];

            $currDate = $startDate->copy();
            while ($currDate->lte($endDate)) {
                $dateStr = $currDate->toDateString();
                foreach ($dailyTasks as $taskItem) {
                    $occurrences[] = [
                        'scheduled_date' => $dateStr,
                        'task_template_id' => $taskItem['task_template_id'],
                        'start_time' => $taskItem['start_time'],
                        'end_time' => $taskItem['end_time'] ?? null,
                        'priority' => $taskItem['priority'] ?? 'normal',
                        'notes' => $taskItem['notes'] ?? ($data['notes'] ?? null),
                    ];
                }
                $currDate->addDay();
            }
        } elseif ($recurrenceType === 'weekly') {
            $startDate = Carbon::parse($data['start_date'])->startOfDay();
            $weeksCount = max(1, min(24, (int) ($data['weeks_count'] ?? 1)));
            $weeklyDays = $data['weekly_days'] ?? [];
            $weeklyTasks = $data['weekly_tasks'] ?? [];

            $dayMap = [
                'sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6,
                '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6,
            ];

            $targetDays = [];
            foreach ($weeklyDays as $d) {
                $key = strtolower(trim((string) $d));
                if (isset($dayMap[$key])) {
                    $targetDays[] = $dayMap[$key];
                }
            }
            $targetDays = array_unique($targetDays);
            sort($targetDays);

            // Generate across weeksCount weeks from start date
            for ($w = 0; $w < $weeksCount; $w++) {
                $weekStart = $startDate->copy()->addWeeks($w);
                foreach ($targetDays as $dayOfWeek) {
                    $candidateDate = $weekStart->copy()->startOfWeek(Carbon::SUNDAY)->addDays($dayOfWeek);
                    if ($candidateDate->gte($startDate)) {
                        $dateStr = $candidateDate->toDateString();
                        foreach ($weeklyTasks as $taskItem) {
                            $occurrences[] = [
                                'scheduled_date' => $dateStr,
                                'task_template_id' => $taskItem['task_template_id'],
                                'start_time' => $taskItem['start_time'],
                                'end_time' => $taskItem['end_time'] ?? null,
                                'priority' => $taskItem['priority'] ?? 'normal',
                                'notes' => $taskItem['notes'] ?? ($data['notes'] ?? null),
                            ];
                        }
                    }
                }
            }
        } elseif ($recurrenceType === 'monthly') {
            $startDate = Carbon::parse($data['start_date'])->startOfDay();
            $monthsCount = max(1, min(12, (int) ($data['months_count'] ?? 1)));
            $monthlyDays = array_values(array_unique(array_map('intval', $data['monthly_days'] ?? [1])));
            $monthlyTasks = $data['monthly_tasks'] ?? [];

            for ($m = 0; $m < $monthsCount; $m++) {
                $monthAnchor = $startDate->copy()->addMonthsNoOverflow($m);
                $daysInMonth = $monthAnchor->daysInMonth;

                // Deduplicate actual days for this month (e.g. 29, 30, 31 in Feb all resolve to 28)
                $actualDaysInMonth = [];
                foreach ($monthlyDays as $dayNum) {
                    $actualDay = min($dayNum, $daysInMonth);
                    $actualDaysInMonth[$actualDay] = true;
                }
                $sortedActualDays = array_keys($actualDaysInMonth);
                sort($sortedActualDays);

                foreach ($sortedActualDays as $actualDay) {
                    $candidateDate = $monthAnchor->copy()->day($actualDay);

                    if ($candidateDate->gte($startDate)) {
                        $dateStr = $candidateDate->toDateString();
                        foreach ($monthlyTasks as $taskItem) {
                            $occurrences[] = [
                                'scheduled_date' => $dateStr,
                                'task_template_id' => $taskItem['task_template_id'],
                                'start_time' => $taskItem['start_time'],
                                'end_time' => $taskItem['end_time'] ?? null,
                                'priority' => $taskItem['priority'] ?? 'normal',
                                'notes' => $taskItem['notes'] ?? ($data['notes'] ?? null),
                            ];
                        }
                    }
                }
            }
        }

        // Sort occurrences chronologically
        usort($occurrences, function ($a, $b) {
            $cmp = strcmp($a['scheduled_date'], $b['scheduled_date']);
            if ($cmp === 0) {
                return strcmp($a['start_time'], $b['start_time']);
            }
            return $cmp;
        });

        $totalCount = count($occurrences);
        if ($totalCount === 0) {
            throw new InvalidArgumentException('পুনরাবৃত্তির শর্ত অনুযায়ী কোনো কাজের তারিখ নির্ধারিত হয়নি।');
        }

        return DB::transaction(function () use ($data, $userId, $recurrenceType, $recurringGroupId, $occurrences, $totalCount) {
            $firstAssignment = null;
            $seq = 1;

            foreach ($occurrences as $item) {
                $assignment = TaskAssignment::create([
                    'recurring_group_id' => $recurringGroupId,
                    'recurrence_type' => $recurrenceType,
                    'recurrence_seq' => $seq,
                    'recurrence_total_count' => $totalCount,
                    'is_recurring' => true,
                    'task_template_id' => $item['task_template_id'],
                    'farmer_id' => $data['farmer_id'],
                    'farm_location_id' => $data['farm_location_id'],
                    'scheduled_date' => $item['scheduled_date'],
                    'start_time' => $item['start_time'],
                    'end_time' => $item['end_time'] ?? null,
                    'priority' => $item['priority'] ?? 'normal',
                    'status' => $data['status'] ?? 'assigned',
                    'notes' => $item['notes'] ?? null,
                    'assigned_by' => $userId,
                    'assigned_at' => now(),
                ]);

                if ($firstAssignment === null) {
                    $firstAssignment = $assignment;
                }
                $seq++;
            }

            $firstAssignment->load(['farmer', 'taskTemplate', 'location']);

            $farmerName = $firstAssignment->farmer->full_name ?? 'কৃষক';
            $locationName = $firstAssignment->location->name ?? 'ফার্ম';
            $recLabel = match ($recurrenceType) {
                'daily' => 'দৈনিক',
                'weekly' => 'সাপ্তাহিক',
                'monthly' => 'মাসিক',
                default => 'নিয়মিত',
            };

            ActivityLog::record(
                action: 'assignment.recurring_created',
                description: "কৃষক {$farmerName}-কে {$recLabel} পুনরাবৃত্তিতে মোট {$totalCount}টি কাজ বরাদ্দ করা হয়েছে (সিরিজ: {$recurringGroupId}, {$locationName})",
                subject: $firstAssignment,
                newValues: [
                    'recurring_group_id' => $recurringGroupId,
                    'recurrence_type' => $recurrenceType,
                    'total_count' => $totalCount,
                ]
            );

            // Single unified notification for the series
            NotificationService::notifyAdmins(new TaskAssignedNotification($firstAssignment));

            if ($firstAssignment->farmer && $firstAssignment->farmer->user) {
                $firstAssignment->farmer->user->notify(new TaskAssignedNotification($firstAssignment));
            }

            DashboardService::clearKpiCache();

            return $firstAssignment;
        });
    }

    /**
     * Reassign an assignment to a new farmer (e.g. if the original farmer is sick).
     * Supports single assignment or the entire future series.
     *
     * @return int Number of updated assignments
     */
    public function reassignFarmer(
        TaskAssignment $assignment,
        int $newFarmerId,
        bool $applyToFutureSeries = false,
        ?string $reason = null,
        ?User $user = null
    ): int {
        $newFarmer = Farmer::findOrFail($newFarmerId);
        $oldFarmerName = $assignment->farmer->full_name ?? 'পূর্বের কৃষক';
        $newFarmerName = $newFarmer->full_name;

        return DB::transaction(function () use ($assignment, $newFarmer, $newFarmerId, $oldFarmerName, $newFarmerName, $applyToFutureSeries, $reason) {
            $reasonNote = $reason ? "[কৃষক অসুস্থ/পরিবর্তন: {$reason}]" : '[কৃষক পরিবর্তন করা হয়েছে]';

            if ($applyToFutureSeries && $assignment->recurring_group_id) {
                $query = TaskAssignment::where('recurring_group_id', $assignment->recurring_group_id)
                    ->whereDate('scheduled_date', '>=', $assignment->scheduled_date->toDateString())
                    ->whereIn('status', ['assigned', 'in_progress']);

                $assignmentsToUpdate = $query->get();
                $count = $assignmentsToUpdate->count();

                foreach ($assignmentsToUpdate as $item) {
                    $itemNotes = $item->notes ? $item->notes."\n".$reasonNote : $reasonNote;
                    $item->update([
                        'farmer_id' => $newFarmerId,
                        'notes' => $itemNotes,
                    ]);
                }

                ActivityLog::record(
                    action: 'assignment.farmer_reassigned_series',
                    description: "কৃষক পরিবর্তনের কারণে সিরিজ {$assignment->recurring_group_id}-এর {$count}টি কাজ {$oldFarmerName} থেকে {$newFarmerName}-কে পুনঃবরাদ্দ করা হয়েছে।",
                    subject: $assignment,
                    newValues: [
                        'recurring_group_id' => $assignment->recurring_group_id,
                        'new_farmer_id' => $newFarmerId,
                        'count' => $count,
                        'reason' => $reason,
                    ]
                );

                if ($newFarmer->user && $assignment) {
                    $assignment->refresh()->load(['farmer', 'taskTemplate', 'location']);
                    $newFarmer->user->notify(new TaskAssignedNotification($assignment));
                }

                DashboardService::clearKpiCache();

                return $count;
            }

            // Single assignment reassign
            $currentNotes = $assignment->notes ? $assignment->notes."\n".$reasonNote : $reasonNote;
            $assignment->update([
                'farmer_id' => $newFarmerId,
                'notes' => $currentNotes,
            ]);

            $assignment->load(['farmer', 'taskTemplate', 'location']);

            ActivityLog::record(
                action: 'assignment.farmer_reassigned',
                description: "কাজ #{$assignment->id} {$oldFarmerName} থেকে নতুন কৃষক {$newFarmerName}-কে পুনঃবরাদ্দ করা হয়েছে।",
                subject: $assignment,
                newValues: [
                    'assignment_id' => $assignment->id,
                    'new_farmer_id' => $newFarmerId,
                    'reason' => $reason,
                ]
            );

            if ($newFarmer->user) {
                $newFarmer->user->notify(new TaskAssignedNotification($assignment));
            }

            DashboardService::clearKpiCache();

            return 1;
        });
    }

    /**
     * Cancel an entire recurring series from a given date onwards.
     */
    public function cancelRecurringSeries(string $recurringGroupId, ?string $fromDate = null, ?string $reason = null, ?User $user = null): int
    {
        return DB::transaction(function () use ($recurringGroupId, $fromDate, $reason) {
            $query = TaskAssignment::where('recurring_group_id', $recurringGroupId)
                ->whereIn('status', ['assigned']);

            if ($fromDate) {
                $query->whereDate('scheduled_date', '>=', $fromDate);
            }

            $assignments = $query->get();
            $count = $assignments->count();

            $reasonNote = $reason ? "[সিরিজ বাতিল: {$reason}]" : '[পুনরাবৃত্তি সিরিজ বাতিল করা হয়েছে]';

            foreach ($assignments as $item) {
                $itemNotes = $item->notes ? $item->notes."\n".$reasonNote : $reasonNote;
                $item->update([
                    'status' => 'cancelled',
                    'notes' => $itemNotes,
                ]);
            }

            ActivityLog::record(
                action: 'assignment.recurring_cancelled',
                description: "সিরিজ {$recurringGroupId}-এর {$count}টি কাজ বাতিল করা হয়েছে।",
                newValues: [
                    'recurring_group_id' => $recurringGroupId,
                    'cancelled_count' => $count,
                    'from_date' => $fromDate,
                    'reason' => $reason,
                ]
            );

            DashboardService::clearKpiCache();

            return $count;
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

            if ($prevStatus !== 'completed' && $newStatus === 'completed') {
                if (! $assignment->started_at) {
                    $updateData['started_at'] = now();
                }
                if (! $assignment->completed_at) {
                    $updateData['completed_at'] = now();
                }
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
