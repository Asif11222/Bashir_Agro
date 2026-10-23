<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    /**
     * Provide comprehensive owner/admin dashboard KPIs and farmer progress metrics.
     */
    public function summary(Request $request): JsonResponse
    {
        $params = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'location_id' => ['sometimes', 'integer', 'min:1'],
        ]);
        $dateParam = $params['date'] ?? today()->toDateString();
        $locationId = isset($params['location_id']) ? (int) $params['location_id'] : null;

        $farmersQuery = Farmer::with('farmLocations')->where('status', 'active');
        if ($locationId) {
            $farmersQuery->whereHas('farmLocations', function ($q) use ($locationId) {
                $q->where('farm_locations.id', $locationId);
            });
        }

        $newFarmersThisMonth = Farmer::where('created_at', '>=', now()->startOfMonth())->count();

        $tasksBaseQuery = TaskAssignment::where('status', '!=', 'cancelled');
        if ($dateParam) {
            $tasksBaseQuery->whereDate('scheduled_date', $dateParam);
        }
        if ($locationId) {
            $tasksBaseQuery->where('farm_location_id', $locationId);
        }

        $totals = $tasksBaseQuery->selectRaw(
            'COUNT(*) AS total, '
            .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed, '
            .'SUM(CASE WHEN status IN (?, ?, ?, ?) THEN 1 ELSE 0 END) AS pending',
            ['completed', 'assigned', 'pending', 'in_progress', 'late']
        )->first();
        $todayTasksCount = (int) $totals->total;
        $completedTasksCount = (int) $totals->completed;
        $pendingTasksCount = (int) $totals->pending;

        $completionPercent = $todayTasksCount > 0
            ? (int) round(($completedTasksCount / $todayTasksCount) * 100)
            : 0;

        $farmers = $farmersQuery
            ->withTaskProgress($dateParam, $locationId)
            ->get()
            ->map(function (Farmer $farmer): array {
                $assignedCount = (int) $farmer->total_tasks;
                $completedCount = (int) $farmer->completed_tasks;
                $progressPercent = $farmer->taskProgressPercent();

                $primaryLocation = $farmer->farmLocations->first();
                $categories = $farmer->farmLocations->pluck('type')->filter()->unique()->values()->all();
                if (empty($categories) && $farmer->primary_activity) {
                    $categories = [$farmer->primary_activity];
                }

                return [
                    'id' => (string) $farmer->id,
                    'farmer_code' => $farmer->farmer_code,
                    'name' => $farmer->full_name,
                    'phone' => $farmer->phone,
                    'region' => $primaryLocation?->name ?? ($farmer->address ?: 'বাংলাদেশ'),
                    'farm_type' => $farmer->primary_activity ?: 'কৃষি চাষ',
                    'progress_percent' => $progressPercent,
                    'is_assigned' => $assignedCount > 0,
                    'land_area' => (float) $farmer->farmLocations->sum('area'),
                    'farming_categories' => $categories,
                    'total_tasks' => $assignedCount,
                    'completed_tasks' => $completedCount,
                ];
            });

        $totalFarmers = $farmers->count();

        $activeLocations = FarmLocation::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'area', 'area_unit']);

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => [
                    'total_farmers' => $totalFarmers,
                    'new_farmers_this_month' => $newFarmersThisMonth,
                    'today_tasks' => $todayTasksCount,
                    'completed_tasks' => $completedTasksCount,
                    'pending_tasks' => $pendingTasksCount,
                    'completion_percent' => $completionPercent,
                ],
                'farmers' => $farmers,
                'locations' => $activeLocations,
            ],
        ]);
    }
}
