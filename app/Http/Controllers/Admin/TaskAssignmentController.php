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
use App\Services\DashboardService;
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
        $viewMode = $request->query('view_mode', 'grouped');
        if (! in_array($viewMode, ['grouped', 'flat'], true)) {
            $viewMode = 'grouped';
        }

        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query = TaskAssignment::with([
            'farmer',
            'taskTemplate',
            'location',
            'assigner',
            'submission',
            'recurringChildren.farmer',
            'recurringChildren.taskTemplate',
            'recurringChildren.location',
            'recurringChildren.submission',
        ]);

        if ($viewMode === 'grouped') {
            // Grouped View: Non-recurring tasks OR the representative row (MIN id) for each recurring series
            $hasActiveFilters = ($search !== '') || $status || $priority || $dateFilter;

            $query->where(function ($topQ) use ($search, $status, $priority, $dateFilter, $customDate, $likeOperator, $hasActiveFilters) {
                // 1. Non-recurring tasks
                $topQ->where(function ($nonRecQ) use ($search, $status, $priority, $dateFilter, $customDate, $likeOperator) {
                    $nonRecQ->where('is_recurring', false);
                    $this->applyFilters($nonRecQ, $search, $status, $priority, $dateFilter, $customDate, $likeOperator);
                })
                // 2. OR Recurring series (represented by the first assignment of that recurring_group_id)
                ->orWhere(function ($recQ) use ($search, $status, $priority, $dateFilter, $customDate, $likeOperator, $hasActiveFilters) {
                    $recQ->where('is_recurring', true)
                        ->whereIn('id', function ($sub) {
                            $sub->selectRaw('MIN(id)')
                                ->from('task_assignments')
                                ->where('is_recurring', true)
                                ->whereNull('deleted_at')
                                ->groupBy('recurring_group_id');
                        });

                    // If filters are active, match if ANY task in the recurring series satisfies the criteria
                    if ($hasActiveFilters) {
                        $groupEloquent = TaskAssignment::query()->whereNotNull('recurring_group_id');
                        $this->applyFilters($groupEloquent, $search, $status, $priority, $dateFilter, $customDate, $likeOperator);
                        $matchedGroupIds = $groupEloquent->pluck('recurring_group_id')->unique()->filter()->values()->all();

                        $recQ->whereIn('recurring_group_id', $matchedGroupIds);
                    }
                });
            });
        } else {
            // Flat View: Regular filter on each individual assignment
            $this->applyFilters($query, $search, $status, $priority, $dateFilter, $customDate, $likeOperator);
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
            ->paginate(15)
            ->withQueryString();

        return view('assignments.index', [
            'activeTab' => 'assignments',
            'title' => 'কাজ বরাদ্দ ব্যবস্থাপনা',
            'assignments' => $assignments,
            'viewMode' => $viewMode,
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
     * Apply common search, status, priority, and date filters to an Eloquent query.
     */
    private function applyFilters($q, string $search, ?string $status, ?string $priority, ?string $dateFilter, ?string $customDate, string $likeOperator): void
    {
        if ($search !== '') {
            $q->where(function ($sub) use ($search, $likeOperator) {
                $sub->whereHas('farmer', function ($fq) use ($search, $likeOperator) {
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

        if ($status && in_array($status, ['assigned', 'in_progress', 'completed', 'cancelled'], true)) {
            $q->where('status', $status);
        }

        if ($priority && in_array($priority, ['normal', 'important', 'urgent'], true)) {
            $q->where('priority', $priority);
        }

        if ($dateFilter === 'today') {
            $q->whereDate('scheduled_date', today());
        } elseif ($dateFilter === 'this_week') {
            $q->whereBetween('scheduled_date', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ]);
        } elseif ($dateFilter === 'custom' && $customDate) {
            $q->whereDate('scheduled_date', $customDate);
        }
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

        $farmerName = $assignment->farmer->full_name ?? 'কৃষক';
        if ($assignment->is_recurring) {
            $recLabel = match ($assignment->recurrence_type) {
                'daily' => 'দৈনিক',
                'weekly' => 'সাপ্তাহিক',
                'monthly' => 'মাসিক',
                default => 'নিয়মিত',
            };
            $msg = "কৃষক {$farmerName}-কে {$recLabel} পুনরাবৃত্তিতে মোট {$assignment->recurrence_total_count}টি কাজের শিডিউল সফলভাবে বরাদ্দ করা হয়েছে।";
        } else {
            $msg = "কৃষক {$farmerName}-কে কাজটি সফলভাবে বরাদ্দ করা হয়েছে।";
        }

        return redirect()->route('admin.assignments.index')
            ->with('success', $msg);
    }

    /**
     * Display the specified task assignment.
     */
    public function show(TaskAssignment $assignment): View
    {
        $assignment->load(['farmer', 'taskTemplate', 'location', 'assigner', 'submission.evidencePhotos']);
        $farmers = Farmer::where('status', 'active')->orderBy('full_name')->get();

        return view('assignments.show', [
            'activeTab' => 'assignments',
            'title' => "কাজের বরাদ্দ বিবরণ — #{$assignment->id}",
            'assignment' => $assignment,
            'farmers' => $farmers,
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

        DashboardService::clearKpiCache();

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

    /**
     * Reassign an assignment (or recurring series) to a new farmer (e.g. if original farmer is sick).
     */
    public function reassign(Request $request, TaskAssignment $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'new_farmer_id' => ['required', 'integer', 'exists:farmers,id'],
            'apply_to_future_series' => ['nullable'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $applyToFuture = $request->boolean('apply_to_future_series', false);
        $count = $this->assignmentService->reassignFarmer(
            assignment: $assignment,
            newFarmerId: (int) $validated['new_farmer_id'],
            applyToFutureSeries: $applyToFuture,
            reason: $validated['reason'] ?? null,
            user: auth()->user()
        );

        $newFarmer = Farmer::find($validated['new_farmer_id']);
        $newFarmerName = $newFarmer->full_name ?? 'নতুন কৃষক';

        $msg = $applyToFuture
            ? "সিরিজের ভবিষ্যৎ {$count}টি কাজ সফলভাবে নতুন কৃষক {$newFarmerName}-কে পুনঃবরাদ্দ করা হয়েছে।"
            : "কাজটি সফলভাবে নতুন কৃষক {$newFarmerName}-কে পুনঃবরাদ্দ করা হয়েছে।";

        return back()->with('success', $msg);
    }

    /**
     * Cancel an entire recurring series from current date onwards.
     */
    public function cancelRecurringSeries(Request $request, string $recurringGroupId): RedirectResponse
    {
        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $count = $this->assignmentService->cancelRecurringSeries(
            recurringGroupId: $recurringGroupId,
            fromDate: $validated['from_date'] ?? null,
            reason: $validated['reason'] ?? null,
            user: auth()->user()
        );

        return back()->with('success', "সিরিজের {$count}টি কাজ সফলভাবে বাতিল করা হয়েছে।");
    }

    /**
     * Soft delete an entire recurring series (or remaining non-completed tasks).
     */
    public function destroyRecurringSeries(Request $request, string $recurringGroupId): RedirectResponse
    {
        $includeCompleted = $request->boolean('include_completed', false);

        $query = TaskAssignment::where('recurring_group_id', $recurringGroupId);
        if (! $includeCompleted) {
            $query->whereIn('status', ['assigned', 'pending', 'in_progress']);
        }

        $assignments = $query->get();
        $count = $assignments->count();

        if ($count === 0) {
            return back()->with('info', 'মুছে ফেলার মতো কোনো কাজের রেকর্ড পাওয়া যায়নি।');
        }

        DB::transaction(function () use ($assignments, $recurringGroupId, $includeCompleted, $count) {
            $assignmentIds = $assignments->pluck('id')->all();

            // Clean related notifications
            foreach ($assignmentIds as $aid) {
                DB::table('notifications')
                    ->where('data', 'like', '%"assignment_id":'.$aid.'%')
                    ->delete();
            }

            foreach ($assignments as $assignment) {
                $assignment->delete();
            }

            $detailMsg = $includeCompleted
                ? "সম্পূর্ণ পুনরাবৃত্তিমূলক সিরিজ (সম্পন্ন কাজসহ) মুছে ফেলা হয়েছে (সিরিজ: {$recurringGroupId}, মোট {$count}টি কাজ)"
                : "পুনরাবৃত্তিমূলক সিরিজের অসম্পূর্ণ কাজগুলো মুছে ফেলা হয়েছে (সিরিজ: {$recurringGroupId}, মোট {$count}টি কাজ)";

            ActivityLog::record(
                action: 'assignment.recurring_deleted',
                description: $detailMsg,
                newValues: [
                    'recurring_group_id' => $recurringGroupId,
                    'deleted_count' => $count,
                    'included_completed' => $includeCompleted,
                ]
            );
        });

        DashboardService::clearKpiCache();

        $successMsg = $includeCompleted
            ? "সম্পূর্ণ সিরিজের সকল {$count}টি কাজ সফলভাবে মুছে ফেলা হয়েছে (সফট ডিলিট)।"
            : "সিরিজের বাকি {$count}টি অসম্পূর্ণ কাজ সফলভাবে মুছে ফেলা হয়েছে (সফট ডিলিট)।";

        return back()->with('success', $successMsg);
    }
}
