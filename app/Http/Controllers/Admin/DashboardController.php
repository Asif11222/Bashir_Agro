<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * Display the dynamic dashboard with PostgreSQL metrics.
     */
    public function index(Request $request): View
    {
        $kpis = $this->dashboardService->getKpis();
        $statusFilter = $request->query('status');
        $todaysAssignments = $this->dashboardService->getTodaysAssignments($statusFilter);
        $recentActivities = $this->dashboardService->getRecentActivities(10);

        return view('dashboard.index', [
            'activeTab' => 'dashboard',
            'title' => 'ড্যাশবোর্ড',
            'activeFarmers' => $kpis['active_farmers'],
            'newFarmersThisMonth' => $kpis['new_farmers_this_month'],
            'todaysTasksCount' => $kpis['todays_tasks_count'],
            'todaysCompletedCount' => $kpis['todays_completed_count'],
            'todaysInProgressCount' => $kpis['todays_in_progress_count'],
            'todaysLateCount' => $kpis['todays_late_count'],
            'activeLocations' => $kpis['active_locations'],
            'locationActivePercent' => $kpis['location_active_percent'],
            'activeTaskTemplates' => $kpis['active_task_templates'],
            'attentionNeeded' => $kpis['attention_needed'],
            'statusFilter' => $statusFilter,
            'todaysAssignments' => $todaysAssignments,
            'recentActivities' => $recentActivities,
        ]);
    }
}
