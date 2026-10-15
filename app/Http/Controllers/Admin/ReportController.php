<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmLocation;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    /**
     * Display consolidated reports and analytics dashboard.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'tasks');
        $dateFilter = $request->query('date_filter', 'this_month');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $search = trim((string) $request->query('search', ''));
        $priority = $request->query('priority');
        $locationId = $request->query('location_id') ? (int) $request->query('location_id') : null;

        $dateRange = $this->reportService->parseDateRange($dateFilter, $startDate, $endDate);
        $kpis = $this->reportService->getOverviewKpis($dateRange['start'], $dateRange['end']);

        $locations = FarmLocation::orderBy('name')->get(['id', 'name', 'location_code']);

        $taskReports = null;
        $farmerReports = null;
        $locationReports = null;
        $statusReport = null;
        $activityReports = null;

        switch ($tab) {
            case 'farmers':
                $farmerReports = $this->reportService->getFarmerActivityReport(
                    $search,
                    $dateRange['start'],
                    $dateRange['end']
                );
                break;

            case 'locations':
                $locationReports = $this->reportService->getLocationPerformanceReport(
                    $search,
                    $dateRange['start'],
                    $dateRange['end']
                );
                break;

            case 'status':
                $statusReport = $this->reportService->getAssignmentStatusReport(
                    $priority,
                    $locationId,
                    $dateRange['start'],
                    $dateRange['end']
                );
                break;

            case 'activity':
                $activityReports = $this->reportService->getSystemActivityReport(
                    $search,
                    $dateRange['start'],
                    $dateRange['end']
                );
                break;

            case 'tasks':
            default:
                $tab = 'tasks';
                $taskReports = $this->reportService->getTaskPerformanceReport(
                    $search,
                    $dateRange['start'],
                    $dateRange['end']
                );
                break;
        }

        return view('reports.index', [
            'activeTab' => 'reports',
            'title' => 'রিপোর্ট ও অ্যানালিটিক্স',
            'tab' => $tab,
            'dateFilter' => $dateFilter,
            'startDate' => $dateRange['start'],
            'endDate' => $dateRange['end'],
            'dateRangeLabel' => $dateRange['label'],
            'search' => $search,
            'priority' => $priority,
            'locationId' => $locationId,
            'locations' => $locations,
            'kpis' => $kpis,
            'taskReports' => $taskReports,
            'farmerReports' => $farmerReports,
            'locationReports' => $locationReports,
            'statusReport' => $statusReport,
            'activityReports' => $activityReports,
        ]);
    }
}
