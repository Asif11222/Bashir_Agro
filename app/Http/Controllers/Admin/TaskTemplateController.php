<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskTemplateRequest;
use App\Http\Requests\UpdateTaskTemplateRequest;
use App\Models\ActivityLog;
use App\Models\TaskTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaskTemplateController extends Controller
{
    /**
     * Display a listing of task templates with search, filters, and pagination.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $totalTasksCount = TaskTemplate::count();
        $activeTasksCount = TaskTemplate::where('status', 'active')->count();
        $inactiveTasksCount = TaskTemplate::where('status', 'inactive')->count();

        $tasksQuery = TaskTemplate::query();

        if ($search) {
            $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $tasksQuery->where(function ($query) use ($search, $likeOperator) {
                $query->where('name', $likeOperator, "%{$search}%")
                    ->orWhere('task_code', $likeOperator, "%{$search}%")
                    ->orWhere('category', $likeOperator, "%{$search}%");
            });
        }

        if ($status && in_array($status, ['active', 'inactive'])) {
            $tasksQuery->where('status', $status);
        }

        $tasks = $tasksQuery->latest('id')->paginate(20)->withQueryString();

        return view('tasks.index', [
            'activeTab' => 'tasks',
            'title' => 'কাজ ব্যবস্থাপনা',
            'tasks' => $tasks,
            'search' => $search,
            'status' => $status,
            'totalTasksCount' => $totalTasksCount,
            'activeTasksCount' => $activeTasksCount,
            'inactiveTasksCount' => $inactiveTasksCount,
        ]);
    }

    /**
     * Show the form for creating a new task template.
     */
    public function create(): View
    {
        return view('tasks.create', [
            'activeTab' => 'tasks',
            'title' => 'নতুন কাজ তৈরি করুন',
        ]);
    }

    /**
     * Store a newly created task template in storage within a transaction.
     */
    public function store(StoreTaskTemplateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $task = DB::transaction(function () use ($request, $validated) {
            $taskCode = TaskTemplate::generateNextTaskCode();

            $status = $validated['status'] ?? 'active';

            $task = TaskTemplate::create([
                'task_code' => $taskCode,
                'name' => $validated['name'],
                'category' => $validated['category'],
                'description' => $validated['description'] ?? null,
                'instructions' => $validated['instructions'],
                'required_photo_count' => $validated['required_photo_count'],
                'require_realtime_photo' => $request->boolean('require_realtime_photo', true),
                'allow_gallery_upload' => $request->boolean('allow_gallery_upload', false),
                'require_geofence' => $request->boolean('require_geofence', true),
                'recurrence_type' => $validated['recurrence_type'] ?? 'once',
                'status' => $status,
                'is_active' => $status === 'active',
                'created_by' => auth()->id(),
            ]);

            ActivityLog::record(
                action: 'task.created',
                description: "নতুন কাজের টেমপ্লেট তৈরি করা হয়েছে: {$task->name} ({$task->task_code})",
                subject: $task,
                newValues: $task->toArray()
            );

            return $task;
        });

        return redirect()->route('admin.tasks.index')
            ->with('success', "কাজের টেমপ্লেট {$task->name} ({$task->task_code}) সফলভাবে তৈরি হয়েছে।");
    }

    /**
     * Display the specified task template.
     */
    public function show(TaskTemplate $task): View
    {
        $task->load([
            'taskAssignments' => function ($query) {
                $query->with(['farmer', 'farmLocation'])->latest()->limit(10);
            },
        ]);

        return view('tasks.show', [
            'activeTab' => 'tasks',
            'title' => "কাজের বিবরণ — {$task->name}",
            'task' => $task,
        ]);
    }

    /**
     * Show the form for editing the specified task template.
     */
    public function edit(TaskTemplate $task): View
    {
        return view('tasks.edit', [
            'activeTab' => 'tasks',
            'title' => "কাজের টেমপ্লেট সম্পাদনা — {$task->name}",
            'task' => $task,
        ]);
    }

    /**
     * Update the specified task template in storage within a transaction.
     */
    public function update(UpdateTaskTemplateRequest $request, TaskTemplate $task): RedirectResponse
    {
        $validated = $request->validated();
        $oldValues = $task->toArray();

        DB::transaction(function () use ($request, $validated, $task, $oldValues) {
            $prevStatus = $task->status;
            $newStatus = $validated['status'];

            $task->update([
                'name' => $validated['name'],
                'category' => $validated['category'],
                'description' => $validated['description'] ?? null,
                'instructions' => $validated['instructions'],
                'required_photo_count' => $validated['required_photo_count'],
                'require_realtime_photo' => $request->boolean('require_realtime_photo', true),
                'allow_gallery_upload' => $request->boolean('allow_gallery_upload', false),
                'require_geofence' => $request->boolean('require_geofence', true),
                'recurrence_type' => $validated['recurrence_type'] ?? $task->recurrence_type ?? 'once',
                'status' => $newStatus,
                'is_active' => $newStatus === 'active',
            ]);

            $action = 'task.updated';
            $description = "কাজের টেমপ্লেট হালনাগাদ করা হয়েছে: {$task->name} ({$task->task_code})";

            if ($prevStatus === 'active' && $newStatus === 'inactive') {
                $action = 'task.deactivated';
                $description = "কাজের টেমপ্লেট নিষ্ক্রিয় করা হয়েছে: {$task->name} ({$task->task_code})";
            } elseif ($prevStatus === 'inactive' && $newStatus === 'active') {
                $action = 'task.activated';
                $description = "কাজের টেমপ্লেট পুনরায় সক্রিয় করা হয়েছে: {$task->name} ({$task->task_code})";
            }

            ActivityLog::record(
                action: $action,
                description: $description,
                subject: $task,
                oldValues: $oldValues,
                newValues: $task->toArray()
            );
        });

        return redirect()->route('admin.tasks.index')
            ->with('success', "কাজের টেমপ্লেট {$task->name}-এর তথ্য সফলভাবে আপডেট করা হয়েছে।");
    }

    /**
     * Remove the specified task template from storage (Soft Delete).
     */
    public function destroy(TaskTemplate $task): RedirectResponse
    {
        $taskName = $task->name;
        $taskCode = $task->task_code;

        DB::transaction(function () use ($task, $taskName, $taskCode) {
            $oldValues = $task->toArray();
            $task->delete();

            ActivityLog::record(
                action: 'task.deleted',
                description: "কাজের টেমপ্লেট মুছে ফেলা হয়েছে (Soft Delete): {$taskName} ({$taskCode})",
                subject: $task,
                oldValues: $oldValues
            );
        });

        return redirect()->route('admin.tasks.index')
            ->with('success', "কাজের টেমপ্লেট {$taskName} ({$taskCode}) সফলভাবে মুছে ফেলা হয়েছে।");
    }
}
