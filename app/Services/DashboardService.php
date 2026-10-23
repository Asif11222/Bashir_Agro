<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Cache key for dashboard KPI metrics.
     */
    public const KPI_CACHE_KEY = 'dashboard_kpis';

    /**
     * Calculate and return key performance indicators (KPIs) from database with caching.
     *
     * @return array<string, int|float>
     */
    public function getKpis(bool $forceRefresh = false): array
    {
        if ($forceRefresh || app()->runningUnitTests()) {
            return $this->calculateKpis();
        }

        return Cache::remember(self::KPI_CACHE_KEY, 30, function () {
            return $this->calculateKpis();
        });
    }

    /**
     * Clear cached dashboard KPIs.
     */
    public static function clearKpiCache(): void
    {
        Cache::forget(self::KPI_CACHE_KEY);
    }

    /**
     * Internal calculation of key performance indicators.
     *
     * @return array<string, int|float>
     */
    protected function calculateKpis(): array
    {
        $activeFarmers = Farmer::where('status', 'active')->count();
        $newFarmersThisMonth = Farmer::where('created_at', '>=', now()->startOfMonth())->count();

        $activeLocations = FarmLocation::where('status', 'active')->count();
        $totalLocations = FarmLocation::count();
        $locationActivePercent = $totalLocations > 0 ? round(($activeLocations / $totalLocations) * 100) : 100;

        $activeTaskTemplates = TaskTemplate::where('status', 'active')->count();

        // One aggregate query feeds all web-dashboard status counters.
        $todayCounts = TaskAssignment::whereDate('scheduled_date', today())->selectRaw(
            'COUNT(*) AS total, '
            .'SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) AS assigned, '
            .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS in_progress, '
            .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed, '
            .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS late, '
            .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cancelled',
            ['assigned', 'pending', 'in_progress', 'completed', 'late', 'cancelled']
        )->first();
        $todaysCancelledCount = (int) $todayCounts->cancelled;
        $todaysTasksCount = (int) $todayCounts->total - $todaysCancelledCount;
        $todaysAssignedCount = (int) $todayCounts->assigned;
        $todaysInProgressCount = (int) $todayCounts->in_progress;
        $todaysCompletedCount = (int) $todayCounts->completed;
        $todaysLateCount = (int) $todayCounts->late;

        $attentionNeeded = TaskAssignment::where('status', 'late')
            ->orWhere(function ($query) {
                $query->whereDate('scheduled_date', '<', today())
                    ->whereNotIn('status', ['completed', 'cancelled']);
            })
            ->count();

        return [
            'active_farmers' => $activeFarmers,
            'new_farmers_this_month' => $newFarmersThisMonth,
            'active_locations' => $activeLocations,
            'total_locations' => $totalLocations,
            'location_active_percent' => $locationActivePercent,
            'active_task_templates' => $activeTaskTemplates,
            'todays_tasks_count' => $todaysTasksCount,
            'todays_assigned_count' => $todaysAssignedCount,
            'todays_in_progress_count' => $todaysInProgressCount,
            'todays_completed_count' => $todaysCompletedCount,
            'todays_late_count' => $todaysLateCount,
            'todays_cancelled_count' => $todaysCancelledCount,
            'attention_needed' => $attentionNeeded,
        ];
    }

    /**
     * Retrieve today's assignments with eager-loaded relationships to prevent N+1 queries.
     *
     * @return Collection<int, TaskAssignment>
     */
    public function getTodaysAssignments(?string $statusFilter = null): Collection
    {
        $query = TaskAssignment::with(['farmer', 'taskTemplate', 'location', 'assigner', 'submission'])
            ->whereDate('scheduled_date', today())
            ->orderBy('start_time', 'asc')
            ->orderBy('id', 'desc');

        if ($statusFilter && in_array($statusFilter, ['assigned', 'in_progress', 'completed', 'cancelled', 'late'])) {
            $query->where('status', $statusFilter);
        } else {
            $query->where('status', '!=', 'cancelled');
        }

        return $query->get();
    }

    /**
     * Retrieve the most recent activity log records.
     *
     * @return Collection<int, ActivityLog>
     */
    public function getRecentActivities(int $limit = 10): Collection
    {
        return ActivityLog::with('user')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
