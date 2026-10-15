<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\TaskAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    /**
     * Display live task monitoring, field validation status, and recent operations.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $priority = $request->query('priority');
        $dateFilter = $request->query('date_filter', 'today'); // Default to today's operations
        $customDate = $request->query('date');

        // Context-aware base query for current search, priority, and date scope
        $baseQuery = TaskAssignment::query();

        // Driver-aware search for PostgreSQL and SQLite
        if ($search !== '') {
            $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $baseQuery->where(function ($q) use ($search, $likeOperator) {
                $q->whereHas('farmer', function ($fq) use ($search, $likeOperator) {
                    $fq->where('full_name', $likeOperator, "%{$search}%")
                        ->orWhere('farmer_code', $likeOperator, "%{$search}%");
                })
                    ->orWhereHas('taskTemplate', function ($tq) use ($search, $likeOperator) {
                        $tq->where('name', $likeOperator, "%{$search}%")
                            ->orWhere('task_code', $likeOperator, "%{$search}%");
                    })
                    ->orWhereHas('location', function ($lq) use ($search, $likeOperator) {
                        $lq->where('name', $likeOperator, "%{$search}%")
                            ->orWhere('location_code', $likeOperator, "%{$search}%");
                    });
            });
        }

        // Priority Filter
        if ($priority && in_array($priority, ['normal', 'important', 'urgent'])) {
            $baseQuery->where('priority', $priority);
        }

        // Date Filter
        if ($dateFilter === 'today') {
            $baseQuery->whereDate('scheduled_date', today());
        } elseif ($dateFilter === 'this_week') {
            $baseQuery->whereBetween('scheduled_date', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ]);
        } elseif ($dateFilter === 'custom' && $customDate) {
            $baseQuery->whereDate('scheduled_date', $customDate);
        }
        // If $dateFilter === 'all', no date constraint is applied.

        // Scoped tab statistics matching the active date & search context
        $scopedTotalCount = (clone $baseQuery)->count();
        $assignedCount = (clone $baseQuery)->whereIn('status', ['assigned', 'pending'])->count();
        $inProgressCount = (clone $baseQuery)->where('status', 'in_progress')->count();
        $completedCount = (clone $baseQuery)->where('status', 'completed')->count();
        $cancelledCount = (clone $baseQuery)->where('status', 'cancelled')->count();
        $lateCount = (clone $baseQuery)->where(function ($q) {
            $q->where('status', 'late')
                ->orWhere(function ($sub) {
                    $sub->whereDate('scheduled_date', '<', today())
                        ->whereNotIn('status', ['completed', 'cancelled']);
                });
        })->count();

        // System-wide counts for operational awareness
        $totalAssignmentsCount = TaskAssignment::count();
        $todaysAssignmentsCount = TaskAssignment::whereDate('scheduled_date', today())->count();
        $globalInProgressCount = TaskAssignment::where('status', 'in_progress')->count();

        // Paginated query with status filter applied
        $query = (clone $baseQuery)->with(['farmer', 'taskTemplate', 'location', 'assigner']);

        if ($status && in_array($status, ['assigned', 'in_progress', 'completed', 'cancelled', 'late'])) {
            if ($status === 'assigned') {
                $query->whereIn('status', ['assigned', 'pending']);
            } elseif ($status === 'late') {
                $query->where(function ($q) {
                    $q->where('status', 'late')
                        ->orWhere(function ($sub) {
                            $sub->whereDate('scheduled_date', '<', today())
                                ->whereNotIn('status', ['completed', 'cancelled']);
                        });
                });
            } else {
                $query->where('status', $status);
            }
        }

        $assignments = $query->orderBy('scheduled_date', 'desc')
            ->orderBy('start_time', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Focal monitoring assignment resolution:
        // 1. Look for in_progress task in the current filtered query
        $activeAssignment = (clone $query)
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->first();

        // 2. If none in current filter, look for ANY active in_progress task system-wide
        $isGlobalActive = false;
        if (! $activeAssignment) {
            $activeAssignment = TaskAssignment::with(['farmer', 'taskTemplate', 'location', 'assigner'])
                ->where('status', 'in_progress')
                ->latest('started_at')
                ->first();
            if ($activeAssignment) {
                $isGlobalActive = true;
            }
        }

        // 3. If no in_progress task exists anywhere, fallback to the first assignment of current view
        $isOngoing = ($activeAssignment && $activeAssignment->status === 'in_progress');
        if (! $activeAssignment) {
            $activeAssignment = (clone $query)->first();
        }

        // Recent activity stream
        $recentActivities = ActivityLog::with('user')
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('monitoring.index', [
            'activeTab' => 'monitor',
            'title' => 'কার্যক্রম পর্যবেক্ষণ',
            'assignments' => $assignments,
            'activeAssignment' => $activeAssignment,
            'isOngoing' => $isOngoing,
            'isGlobalActive' => $isGlobalActive,
            'recentActivities' => $recentActivities,
            'search' => $search,
            'status' => $status,
            'priority' => $priority,
            'dateFilter' => $dateFilter,
            'customDate' => $customDate,
            'scopedTotalCount' => $scopedTotalCount,
            'assignedCount' => $assignedCount,
            'inProgressCount' => $inProgressCount,
            'completedCount' => $completedCount,
            'lateCount' => $lateCount,
            'cancelledCount' => $cancelledCount,
            'totalAssignmentsCount' => $totalAssignmentsCount,
            'todaysAssignmentsCount' => $todaysAssignmentsCount,
            'globalInProgressCount' => $globalInProgressCount,
        ]);
    }
}
