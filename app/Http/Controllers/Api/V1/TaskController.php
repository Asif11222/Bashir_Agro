<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\TaskTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of available active task templates.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TaskTemplate::where('is_active', true)->where('status', 'active');

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        $tasks = $query->orderBy('name')->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => TaskResource::collection($tasks),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    /**
     * Display the specified task template.
     */
    public function show(TaskTemplate $task): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new TaskResource($task),
        ]);
    }
}
