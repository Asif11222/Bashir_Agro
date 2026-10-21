<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    /**
     * Perform global search across Farmers, Locations, Tasks, and Assignments.
     */
    public function search(Request $request): JsonResponse
    {
        $rawQuery = trim((string) $request->query('q', ''));

        if (mb_strlen($rawQuery) < 2) {
            return response()->json([
                'query' => $rawQuery,
                'total' => 0,
                'groups' => [],
            ]);
        }

        $term = '%'.mb_strtolower($rawQuery).'%';
        $groups = [];
        $totalResults = 0;

        // 1. Farmers
        $farmers = Farmer::query()
            ->where(function ($query) use ($term) {
                $query->whereRaw('LOWER(full_name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(phone) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(farmer_code) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(nid) LIKE ?', [$term]);
            })
            ->limit(5)
            ->get();

        if ($farmers->isNotEmpty()) {
            $totalResults += $farmers->count();
            $groups[] = [
                'name' => 'কৃষক',
                'icon' => '♟',
                'items' => $farmers->map(function (Farmer $farmer) {
                    $statusText = $farmer->status === 'active' ? 'সক্রিয়' : 'নিষ্ক্রিয়';
                    $sub = array_filter([
                        $farmer->farmer_code,
                        $farmer->phone,
                        $statusText,
                    ]);

                    return [
                        'id' => $farmer->id,
                        'title' => $farmer->full_name,
                        'subtitle' => implode(' • ', $sub),
                        'url' => route('admin.farmers.show', $farmer),
                        'badge' => 'কৃষক',
                    ];
                }),
            ];
        }

        // 2. Farm Locations
        $locations = FarmLocation::query()
            ->where(function ($query) use ($term) {
                $query->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(location_code) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(address) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(type) LIKE ?', [$term]);
            })
            ->limit(5)
            ->get();

        if ($locations->isNotEmpty()) {
            $totalResults += $locations->count();
            $groups[] = [
                'name' => 'ফার্ম লোকেশন',
                'icon' => '⌖',
                'items' => $locations->map(function (FarmLocation $loc) {
                    $sub = array_filter([
                        $loc->location_code,
                        $loc->address ?: $loc->type ?: 'ফার্ম লোকেশন',
                    ]);

                    return [
                        'id' => $loc->id,
                        'title' => $loc->name,
                        'subtitle' => implode(' • ', $sub),
                        'url' => route('admin.locations.show', $loc),
                        'badge' => 'লোকেশন',
                    ];
                }),
            ];
        }

        // 3. Task Templates
        $tasks = TaskTemplate::query()
            ->where(function ($query) use ($term) {
                $query->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(task_code) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(category) LIKE ?', [$term]);
            })
            ->limit(5)
            ->get();

        if ($tasks->isNotEmpty()) {
            $totalResults += $tasks->count();
            $groups[] = [
                'name' => 'কাজের টেমপ্লেট',
                'icon' => '▣',
                'items' => $tasks->map(function (TaskTemplate $task) {
                    $sub = array_filter([
                        $task->task_code,
                        $task->category,
                    ]);

                    return [
                        'id' => $task->id,
                        'title' => $task->name,
                        'subtitle' => implode(' • ', $sub),
                        'url' => route('admin.tasks.show', $task),
                        'badge' => 'টেমপ্লেট',
                    ];
                }),
            ];
        }

        // 4. Task Assignments
        $assignments = TaskAssignment::query()
            ->with(['farmer', 'taskTemplate', 'farmLocation'])
            ->where(function ($query) use ($term) {
                $query->whereHas('farmer', function ($sub) use ($term) {
                    $sub->whereRaw('LOWER(full_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(phone) LIKE ?', [$term]);
                })
                    ->orWhereHas('taskTemplate', function ($sub) use ($term) {
                        $sub->whereRaw('LOWER(name) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(task_code) LIKE ?', [$term]);
                    })
                    ->orWhereHas('farmLocation', function ($sub) use ($term) {
                        $sub->whereRaw('LOWER(name) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(location_code) LIKE ?', [$term]);
                    });
            })
            ->limit(5)
            ->get();

        if ($assignments->isNotEmpty()) {
            $totalResults += $assignments->count();
            $groups[] = [
                'name' => 'কাজের বরাদ্দ',
                'icon' => '⚡',
                'items' => $assignments->map(function (TaskAssignment $assignment) {
                    $taskName = $assignment->taskTemplate?->name ?? 'কাজ';
                    $farmerName = $assignment->farmer?->full_name ?? 'কৃষক';
                    $locName = $assignment->farmLocation?->name ?? '';
                    $dateStr = $assignment->scheduled_date ? $assignment->scheduled_date->format('d/m/Y') : '';

                    $sub = array_filter([
                        $locName,
                        $assignment->status,
                        $dateStr,
                    ]);

                    return [
                        'id' => $assignment->id,
                        'title' => "{$taskName} → {$farmerName}",
                        'subtitle' => implode(' • ', $sub),
                        'url' => route('admin.assignments.show', $assignment),
                        'badge' => 'বরাদ্দ',
                    ];
                }),
            ];
        }

        return response()->json([
            'query' => $rawQuery,
            'total' => $totalResults,
            'groups' => $groups,
        ]);
    }
}
