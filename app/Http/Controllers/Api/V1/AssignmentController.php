<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Models\TaskAssignment;
use App\Services\TaskAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(
        protected TaskAssignmentService $assignmentService
    ) {}

    /**
     * Display a listing of assigned tasks for the authenticated farmer or all tasks for admins.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = TaskAssignment::with(['taskTemplate', 'location', 'farmer', 'submission.evidencePhotos'])->latest('scheduled_date');

        // Scope to farmer if requester is a farmer
        if ($user->isFarmer() && $user->farmerProfile) {
            $query->where('farmer_id', $user->farmerProfile->id);
        } elseif ($request->filled('farmer_id')) {
            $query->where('farmer_id', $request->query('farmer_id'));
        }

        // Status filter (assigned, in_progress, completed, etc.)
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Date filter: single date, range, or period filter
        if ($request->filled('date')) {
            $query->whereDate('scheduled_date', $request->query('date'));
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('scheduled_date', [$request->query('start_date'), $request->query('end_date')]);
        } elseif ($request->filled('filter')) {
            $filter = $request->query('filter');
            if ($filter === 'today') {
                $query->whereDate('scheduled_date', today());
            } elseif ($filter === 'week' || $filter === '1_week') {
                $query->whereBetween('scheduled_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()]);
            } elseif ($filter === 'month' || $filter === '1_month') {
                $query->whereBetween('scheduled_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);
            }
        }

        // Location filter
        if ($request->filled('location_id')) {
            $query->where('farm_location_id', $request->query('location_id'));
        } elseif ($request->filled('farm_location_id')) {
            $query->where('farm_location_id', $request->query('farm_location_id'));
        }

        $assignments = $query->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => AssignmentResource::collection($assignments),
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'total' => $assignments->total(),
            ],
        ]);
    }

    /**
     * Display the specified task assignment.
     */
    public function show(Request $request, TaskAssignment $assignment): JsonResponse
    {
        $user = $request->user();

        // Check ownership if farmer
        if ($user->isFarmer() && $user->farmerProfile && $assignment->farmer_id !== $user->farmerProfile->id) {
            return response()->json([
                'success' => false,
                'message' => 'এই কাজের বিবরণ দেখার অনুমতি আপনার নেই।',
            ], 403);
        }

        $assignment->load(['taskTemplate', 'location', 'farmer', 'submission.evidencePhotos']);

        return response()->json([
            'success' => true,
            'data' => new AssignmentResource($assignment),
        ]);
    }

    /**
     * Start the task assignment (transition to in_progress).
     */
    public function start(Request $request, TaskAssignment $assignment): JsonResponse
    {
        $user = $request->user();

        if ($user->isFarmer() && $user->farmerProfile && $assignment->farmer_id !== $user->farmerProfile->id) {
            return response()->json([
                'success' => false,
                'message' => 'এই কাজ শুরু করার অনুমতি আপনার নেই।',
            ], 403);
        }

        if ($assignment->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'কাজটি ইতিমধ্যে সম্পন্ন হয়েছে।',
            ], 422);
        }

        $updated = $this->assignmentService->updateStatus($assignment, 'in_progress', [], $user);

        return response()->json([
            'success' => true,
            'message' => 'কাজটি চলমান হিসেবে চিহ্নিত করা হয়েছে।',
            'data' => new AssignmentResource($updated),
        ]);
    }
}
