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
        $dateParam = $request->query('date');
        $locationId = $request->query('location_id');

        $farmersQuery = Farmer::with('farmLocations')->where('status', 'active');
        if ($locationId) {
            $farmersQuery->whereHas('farmLocations', function ($q) use ($locationId) {
                $q->where('farm_locations.id', $locationId);
            });
        }

        $totalFarmers = (clone $farmersQuery)->count();
        $newFarmersThisMonth = Farmer::where('created_at', '>=', now()->startOfMonth())->count();

        $tasksBaseQuery = TaskAssignment::query();
        if ($dateParam) {
            $tasksBaseQuery->whereDate('scheduled_date', $dateParam);
        }
        if ($locationId) {
            $tasksBaseQuery->where('farm_location_id', $locationId);
        }

        $todayTasksCount = (clone $tasksBaseQuery)->count();
        $completedTasksCount = (clone $tasksBaseQuery)->where('status', 'completed')->count();
        $pendingTasksCount = (clone $tasksBaseQuery)->whereIn('status', ['assigned', 'pending', 'in_progress', 'late'])->count();

        $completionPercent = $todayTasksCount > 0
            ? (int) round(($completedTasksCount / $todayTasksCount) * 100)
            : 0;

        $farmers = $farmersQuery->get()->map(function (Farmer $farmer) use ($dateParam, $locationId): array {
            $farmerTasksQuery = TaskAssignment::where('farmer_id', $farmer->id);
            if ($dateParam) {
                $farmerTasksQuery->whereDate('scheduled_date', $dateParam);
            }
            if ($locationId) {
                $farmerTasksQuery->where('farm_location_id', $locationId);
            }

            $assignedCount = (clone $farmerTasksQuery)->count();
            $completedCount = (clone $farmerTasksQuery)->where('status', 'completed')->count();

            $progressPercent = $assignedCount > 0
                ? (int) round(($completedCount / $assignedCount) * 100)
                : ($completedCount > 0 ? 100 : 0);

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
                'land_area' => (float) ($farmer->farmLocations->sum('area') ?: 4.0),
                'farming_categories' => ! empty($categories) ? $categories : ['সবজি'],
                'total_tasks' => $assignedCount,
                'completed_tasks' => $completedCount,
            ];
        });

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
