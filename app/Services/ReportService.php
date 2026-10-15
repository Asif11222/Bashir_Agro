<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Parse date filter into start and end date strings.
     *
     * @return array{start: ?string, end: ?string, label: string}
     */
    public function parseDateRange(?string $dateFilter, ?string $startDate = null, ?string $endDate = null): array
    {
        return match ($dateFilter) {
            'today' => [
                'start' => today()->toDateString(),
                'end' => today()->toDateString(),
                'label' => 'আজকের রিপোর্ট ('.today()->format('d M, Y').')',
            ],
            'yesterday' => [
                'start' => today()->subDay()->toDateString(),
                'end' => today()->subDay()->toDateString(),
                'label' => 'গতকালের রিপোর্ট ('.today()->subDay()->format('d M, Y').')',
            ],
            'this_week' => [
                'start' => now()->startOfWeek()->toDateString(),
                'end' => now()->endOfWeek()->toDateString(),
                'label' => 'এই সপ্তাহের রিপোর্ট ('.now()->startOfWeek()->format('d M').' - '.now()->endOfWeek()->format('d M, Y').')',
            ],
            'this_month' => [
                'start' => now()->startOfMonth()->toDateString(),
                'end' => now()->endOfMonth()->toDateString(),
                'label' => 'চলতি মাসের রিপোর্ট ('.now()->format('F Y').')',
            ],
            'custom' => [
                'start' => $startDate ?: now()->startOfMonth()->toDateString(),
                'end' => $endDate ?: today()->toDateString(),
                'label' => 'কাস্টম সময়কাল ('.($startDate ?: now()->startOfMonth()->format('d/m/Y')).' থেকে '.($endDate ?: today()->format('d/m/Y')).')',
            ],
            default => [
                'start' => null,
                'end' => null,
                'label' => 'সর্বকালের রিপোর্ট (All Time)',
            ],
        };
    }

    /**
     * Get high-level KPI overview across all reporting metrics.
     *
     * @return array<string, int|float>
     */
    public function getOverviewKpis(?string $startDate = null, ?string $endDate = null): array
    {
        $assignmentQuery = TaskAssignment::query();
        $this->applyDateScope($assignmentQuery, 'scheduled_date', $startDate, $endDate);

        $totalAssignments = (clone $assignmentQuery)->count();
        $completedAssignments = (clone $assignmentQuery)->where('status', 'completed')->count();
        $inProgressAssignments = (clone $assignmentQuery)->where('status', 'in_progress')->count();
        $cancelledAssignments = (clone $assignmentQuery)->where('status', 'cancelled')->count();
        $lateAssignments = (clone $assignmentQuery)->where('status', 'late')->count();

        $completionRate = $totalAssignments > 0
            ? round(($completedAssignments / $totalAssignments) * 100, 1)
            : 0;

        $activeFarmers = Farmer::where('status', 'active')->count();
        $activeLocations = FarmLocation::where('status', 'active')->count();

        return [
            'total_assignments' => $totalAssignments,
            'completed_assignments' => $completedAssignments,
            'in_progress_assignments' => $inProgressAssignments,
            'cancelled_assignments' => $cancelledAssignments,
            'late_assignments' => $lateAssignments,
            'completion_rate' => $completionRate,
            'active_farmers' => $activeFarmers,
            'active_locations' => $activeLocations,
        ];
    }

    /**
     * Generate Task Performance Report.
     */
    public function getTaskPerformanceReport(
        ?string $search = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query = TaskTemplate::query()
            ->withCount([
                'taskAssignments as total_assignments' => function (Builder $q) use ($startDate, $endDate) {
                    $this->applyDateScope($q, 'scheduled_date', $startDate, $endDate);
                },
                'taskAssignments as completed_assignments' => function (Builder $q) use ($startDate, $endDate) {
                    $q->where('status', 'completed');
                    $this->applyDateScope($q, 'scheduled_date', $startDate, $endDate);
                },
                'taskAssignments as in_progress_assignments' => function (Builder $q) use ($startDate, $endDate) {
                    $q->where('status', 'in_progress');
                    $this->applyDateScope($q, 'scheduled_date', $startDate, $endDate);
                },
                'taskAssignments as cancelled_assignments' => function (Builder $q) use ($startDate, $endDate) {
                    $q->where('status', 'cancelled');
                    $this->applyDateScope($q, 'scheduled_date', $startDate, $endDate);
                },
            ]);

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search, $like) {
                $q->where('name', $like, "%{$search}%")
                    ->orWhere('task_code', $like, "%{$search}%")
                    ->orWhere('category', $like, "%{$search}%");
            });
        }

        $paginator = $query->orderBy('total_assignments', 'desc')
            ->orderBy('name', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->getCollection()->transform(function ($task) {
            $total = (int) $task->total_assignments;
            $completed = (int) $task->completed_assignments;
            $task->completion_percentage = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

            return $task;
        });

        return $paginator;
    }

    /**
     * Generate Farmer Activity Report.
     */
    public function getFarmerActivityReport(
        ?string $search = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query = Farmer::query()
            ->withCount([
                'taskAssignments as total_assigned_tasks' => function (Builder $q) use ($startDate, $endDate) {
                    $this->applyDateScope($q, 'scheduled_date', $startDate, $endDate);
                },
                'taskAssignments as completed_tasks' => function (Builder $q) use ($startDate, $endDate) {
                    $q->where('status', 'completed');
                    $this->applyDateScope($q, 'scheduled_date', $startDate, $endDate);
                },
                'taskAssignments as pending_tasks' => function (Builder $q) use ($startDate, $endDate) {
                    $q->whereIn('status', ['assigned', 'in_progress']);
                    $this->applyDateScope($q, 'scheduled_date', $startDate, $endDate);
                },
            ]);

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search, $like) {
                $q->where('full_name', $like, "%{$search}%")
                    ->orWhere('farmer_code', $like, "%{$search}%")
                    ->orWhere('primary_activity', $like, "%{$search}%")
                    ->orWhere('phone', $like, "%{$search}%");
            });
        }

        $paginator = $query->orderBy('total_assigned_tasks', 'desc')
            ->orderBy('full_name', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->getCollection()->transform(function ($farmer) {
            $total = (int) $farmer->total_assigned_tasks;
            $completed = (int) $farmer->completed_tasks;
            $farmer->completion_rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

            return $farmer;
        });

        return $paginator;
    }

    /**
     * Generate Location Performance Report.
     */
    public function getLocationPerformanceReport(
        ?string $search = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query = FarmLocation::query()
            ->withCount([
                'taskAssignments as assigned_tasks' => function (Builder $q) use ($startDate, $endDate) {
                    $this->applyDateScope($q, 'scheduled_date', $startDate, $endDate);
                },
                'taskAssignments as completed_tasks' => function (Builder $q) use ($startDate, $endDate) {
                    $q->where('status', 'completed');
                    $this->applyDateScope($q, 'scheduled_date', $startDate, $endDate);
                },
                'farmers as active_farmers' => function (Builder $q) {
                    $q->where('status', 'active');
                },
            ]);

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search, $like) {
                $q->where('name', $like, "%{$search}%")
                    ->orWhere('location_code', $like, "%{$search}%")
                    ->orWhere('type', $like, "%{$search}%");
            });
        }

        $paginator = $query->orderBy('assigned_tasks', 'desc')
            ->orderBy('name', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->getCollection()->transform(function ($location) {
            $total = (int) $location->assigned_tasks;
            $completed = (int) $location->completed_tasks;
            $location->completion_rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

            return $location;
        });

        return $paginator;
    }

    /**
     * Generate Assignment Status Breakdown Report with filters.
     *
     * @return array<string, mixed>
     */
    public function getAssignmentStatusReport(
        ?string $priority = null,
        ?int $locationId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $query = TaskAssignment::query();
        $this->applyDateScope($query, 'scheduled_date', $startDate, $endDate);

        if ($priority && in_array($priority, ['normal', 'important', 'urgent'])) {
            $query->where('priority', $priority);
        }

        if ($locationId) {
            $query->where('farm_location_id', $locationId);
        }

        $total = (clone $query)->count();
        $assigned = (clone $query)->where('status', 'assigned')->count();
        $inProgress = (clone $query)->where('status', 'in_progress')->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $cancelled = (clone $query)->where('status', 'cancelled')->count();
        $late = (clone $query)->where('status', 'late')->count();

        return [
            'total' => $total,
            'assigned' => $assigned,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'late' => $late,
            'assigned_pct' => $total > 0 ? round(($assigned / $total) * 100, 1) : 0,
            'in_progress_pct' => $total > 0 ? round(($inProgress / $total) * 100, 1) : 0,
            'completed_pct' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'cancelled_pct' => $total > 0 ? round(($cancelled / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Generate System Activity Report.
     */
    public function getSystemActivityReport(
        ?string $search = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query = ActivityLog::with('user');
        $this->applyDateScope($query, 'created_at', $startDate, $endDate);

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search, $like) {
                $q->where('action', $like, "%{$search}%")
                    ->orWhere('description', $like, "%{$search}%")
                    ->orWhere('subject_type', $like, "%{$search}%");
            });
        }

        return $query->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Apply date range filtering safely.
     */
    private function applyDateScope(Builder $query, string $column, ?string $startDate, ?string $endDate): void
    {
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                $query->whereDate($column, $startDate);
            } else {
                $query->whereDate($column, '>=', $startDate)
                    ->whereDate($column, '<=', $endDate);
            }
        } elseif ($startDate) {
            $query->whereDate($column, '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate($column, '<=', $endDate);
        }
    }
}
