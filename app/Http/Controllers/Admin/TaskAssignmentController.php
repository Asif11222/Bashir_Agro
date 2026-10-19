<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskAssignmentRequest;
use App\Http\Requests\UpdateTaskAssignmentRequest;
use App\Models\ActivityLog;
use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Notifications\SystemNotification;
use App\Notifications\TaskCancelledNotification;
use App\Services\TaskAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaskAssignmentController extends Controller
{
    public function __construct(
        protected TaskAssignmentService $assignmentService
    ) {}

    /**
     * Display a listing of task assignments with search, filter, and pagination.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $priority = $request->query('priority');
        $dateFilter = $request->query('date_filter');
        $customDate = $request->query('date');

        $query = TaskAssignment::with(['farmer', 'taskTemplate', 'location', 'assigner']);

        // Driver-aware search for PostgreSQL and SQLite
        if ($search !== '') {
            $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $likeOperator) {
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

        // Status Filter
        if ($status && in_array($status, ['assigned', 'in_progress', 'completed', 'cancelled'])) {
            $query->where('status', $status);
        }

        // Priority Filter
        if ($priority && in_array($priority, ['normal', 'important', 'urgent'])) {
            $query->where('priority', $priority);
        }

        // Date Filter
        if ($dateFilter === 'today') {
            $query->whereDate('scheduled_date', today());
        } elseif ($dateFilter === 'this_week') {
            $query->whereBetween('scheduled_date', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ]);
        } elseif ($dateFilter === 'custom' && $customDate) {
            $query->whereDate('scheduled_date', $customDate);
        }

        // Status counts for tabs
        $totalAssignmentsCount = TaskAssignment::count();
        $assignedCount = TaskAssignment::where('status', 'assigned')->count();
        $inProgressCount = TaskAssignment::where('status', 'in_progress')->count();
        $completedCount = TaskAssignment::where('status', 'completed')->count();
        $cancelledCount = TaskAssignment::where('status', 'cancelled')->count();

        $assignments = $query->orderBy('scheduled_date', 'desc')
            ->orderBy('start_time', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('assignments.index', [
            'activeTab' => 'assignments',
            'title' => 'কাজ বরাদ্দ ব্যবস্থাপনা',
            'assignments' => $assignments,
            'search' => $search,
            'status' => $status,
            'priority' => $priority,
            'dateFilter' => $dateFilter,
            'customDate' => $customDate,
            'totalAssignmentsCount' => $totalAssignmentsCount,
            'assignedCount' => $assignedCount,
            'inProgressCount' => $inProgressCount,
            'completedCount' => $completedCount,
            'cancelledCount' => $cancelledCount,
        ]);
    }

    /**
     * Show the form for creating a new task assignment.
     */
    public function create(Request $request): View
    {
        $farmers = Farmer::where('status', 'active')->orderBy('full_name')->get();
        $tasks = TaskTemplate::where('status', 'active')->orderBy('name')->get();
        $locations = FarmLocation::where('status', 'active')->orderBy('name')->get();

        $selectedFarmerId = $request->query('farmer_id');
        $selectedTaskId = $request->query('task_id');
        $selectedLocationId = $request->query('location_id');

        return view('assignments.create', [
            'activeTab' => 'assignments',
            'title' => 'নতুন কাজ বরাদ্দ করুন',
            'farmers' => $farmers,
            'tasks' => $tasks,
            'locations' => $locations,
            'selectedFarmerId' => $selectedFarmerId,
            'selectedTaskId' => $selectedTaskId,
            'selectedLocationId' => $selectedLocationId,
        ]);
    }

    /**
     * Store a newly created task assignment in storage within a transaction.
     */
    public function store(StoreTaskAssignmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $assignment = $this->assignmentService->createAssignment($validated, auth()->user());

        return redirect()->route('admin.assignments.index')
            ->with('success', "কৃষক {$assignment->farmer->full_name}-কে কাজটি সফলভাবে বরাদ্দ করা হয়েছে।");
    }

    /**
     * Display the specified task assignment.
     */
    public function show(TaskAssignment $assignment): View
    {
        $assignment->load(['farmer', 'taskTemplate', 'location', 'assigner', 'submission.evidencePhotos']);

        return view('assignments.show', [
            'activeTab' => 'assignments',
            'title' => "কাজের বরাদ্দ বিবরণ — #{$assignment->id}",
            'assignment' => $assignment,
        ]);
    }

    /**
     * Show the form for editing the specified task assignment.
     */
    public function edit(TaskAssignment $assignment): View
    {
        $assignment->load(['farmer', 'taskTemplate', 'location']);

        $farmers = Farmer::orderBy('full_name')->get();
        $tasks = TaskTemplate::orderBy('name')->get();
        $locations = FarmLocation::orderBy('name')->get();

        return view('assignments.edit', [
            'activeTab' => 'assignments',
            'title' => "কাজ বরাদ্দ সম্পাদন — #{$assignment->id}",
            'assignment' => $assignment,
            'farmers' => $farmers,
            'tasks' => $tasks,
            'locations' => $locations,
        ]);
    }

    /**
     * Update the specified task assignment in storage within a transaction.
     */
    public function update(UpdateTaskAssignmentRequest $request, TaskAssignment $assignment): RedirectResponse
    {
        $validated = $request->validated();
        $newStatus = $validated['status'];
        unset($validated['status']);

        $this->assignmentService->updateStatus($assignment, $newStatus, $validated, auth()->user());

        return redirect()->route('admin.assignments.index')
            ->with('success', 'কাজ বরাদ্দের তথ্য সফলভাবে হালনাগাদ করা হয়েছে।');
    }

    /**
     * Remove the specified task assignment from storage (Soft Delete).
     */
    public function destroy(TaskAssignment $assignment): RedirectResponse
    {
        $assignment->load(['farmer.user', 'taskTemplate', 'location']);
        $farmerName = $assignment->farmer->full_name ?? 'কৃষক';
        $taskName = $assignment->taskTemplate->name ?? 'কাজ';

        DB::transaction(function () use ($assignment, $farmerName, $taskName) {
            $oldValues = $assignment->toArray();

            // Clean up related previous notifications for this assignment
            DB::table('notifications')
                ->where('data', 'like', '%"assignment_id":'.$assignment->id.'%')
                ->orWhere('data', 'like', '%"assignment_id":'.'"'.$assignment->id.'"'.'%')
                ->delete();

            // Notify the assigned farmer that the assignment has been cancelled / deleted
            if ($assignment->farmer && $assignment->farmer->user) {
                $assignment->farmer->user->notify(new TaskCancelledNotification($assignment));
            }

            $assignment->delete();

            ActivityLog::record(
                action: 'assignment.deleted',
                description: "কাজের বরাদ্দ মুছে ফেলা হয়েছে: {$taskName} ({$farmerName})",
                subject: $assignment,
                oldValues: $oldValues
            );
        });

        return redirect()->route('admin.assignments.index')
            ->with('success', 'কাজের বরাদ্দটি সফলভাবে মুছে ফেলা হয়েছে (সফট ডিলিট)।');
    }

    /**
     * Review and verify a farmer's task submission.
     */
    public function reviewSubmission(Request $request, TaskAssignment $assignment): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'verification_status' => ['required', 'string', 'in:approved,rejected,pending'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $submission = $assignment->submission;
        if (! $submission) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'এই কাজের কোনো জমা প্রতিবেদন পাওয়া যায়নি।',
                ], 404);
            }

            return back()->with('error', 'এই কাজের কোনো জমা প্রতিবেদন পাওয়া যায়নি।');
        }

        $oldStatus = $submission->verification_status;
        $submission->update([
            'verification_status' => $validated['verification_status'],
            'validation_status' => match ($validated['verification_status']) {
                'approved' => 'valid',
                'rejected' => 'invalid',
                default => 'pending',
            },
            'notes' => $validated['review_notes'] ?: $submission->notes,
        ]);

        // When rejected, reset assignment status back to in_progress so the farmer can rework & resubmit
        if ($validated['verification_status'] === 'rejected') {
            $this->assignmentService->updateStatus($assignment, 'in_progress', [
                'completed_at' => null,
            ], auth()->user());
        } elseif ($validated['verification_status'] === 'approved' && $assignment->status !== 'completed') {
            $this->assignmentService->updateStatus($assignment, 'completed', [], auth()->user());
        }

        ActivityLog::record(
            action: 'submission.reviewed',
            description: "কাজের জমা প্রতিবেদন যাচাই সম্পন্ন: #{$assignment->id} ({$oldStatus} → {$validated['verification_status']})",
            subject: $assignment,
            newValues: $submission->toArray()
        );

        $statusText = match ($validated['verification_status']) {
            'approved' => 'অনুমোদিত',
            'rejected' => 'প্রত্যাখ্যাত',
            default => 'পর্যালোচনাধীন',
        };

        // Notify the assigned farmer if account exists
        $assignment->load(['farmer.user', 'taskTemplate']);
        if ($assignment->farmer?->user) {
            $title = $validated['verification_status'] === 'approved' ? 'কাজের প্রতিবেদন অনুমোদিত' : 'কাজের প্রতিবেদন প্রত্যাখ্যাত';
            $message = $validated['verification_status'] === 'approved'
                ? "আপনার জমা দেওয়া কাজ #{$assignment->id} ({$assignment->taskTemplate?->name}) অনুমোদিত হয়েছে।"
                : "আপনার জমা দেওয়া কাজ #{$assignment->id} ({$assignment->taskTemplate?->name}) প্রত্যাখ্যাত হয়েছে। নির্দেশ: ".($validated['review_notes'] ?: 'প্রয়োজনীয় সংশোধন করে পুনরায় জমা দিন।');

            $assignment->farmer->user->notify(new SystemNotification(
                title: $title,
                message: $message,
                module: 'assignments',
                actionUrl: "/assignments/{$assignment->id}"
            ));
        }

        $message = "জমা প্রতিবেদন সফলভাবে {$statusText} করা হয়েছে।";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'verification_status' => $validated['verification_status'],
                'status_text' => $statusText,
                'assignment_status' => $assignment->status,
                'review_notes' => $submission->notes,
                'updated_at' => now()->format('d M, Y h:i A'),
            ]);
        }

        return redirect()->to(route('admin.assignments.show', $assignment).'#review-section')
            ->with('success', $message);
    }
}
