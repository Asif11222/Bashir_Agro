<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFarmerRequest;
use App\Http\Requests\UpdateFarmerRequest;
use App\Models\ActivityLog;
use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\DashboardService;
use App\Notifications\FarmerCreatedNotification;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FarmerController extends Controller
{
    /**
     * Display a listing of farmers with search, filters, and pagination.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $totalFarmersCount = Farmer::count();
        $activeFarmersCount = Farmer::where('status', 'active')->count();
        $inactiveFarmersCount = Farmer::where('status', 'inactive')->count();

        $farmersQuery = Farmer::with(['emergencyContact'])
            ->withTaskProgress(today()->toDateString())
            ->withCount('farmLocations');

        if ($search) {
            $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $farmersQuery->where(function ($query) use ($search, $likeOperator) {
                $query->where('full_name', $likeOperator, "%{$search}%")
                    ->orWhere('farmer_code', $likeOperator, "%{$search}%")
                    ->orWhere('phone', $likeOperator, "%{$search}%");
            });
        }

        if ($status && in_array($status, ['active', 'inactive'])) {
            $farmersQuery->where('status', $status);
        }

        $farmers = $farmersQuery->latest('id')->paginate(20)->withQueryString();

        return view('farmers.index', [
            'activeTab' => 'farmers',
            'title' => 'কৃষক ব্যবস্থাপনা',
            'farmers' => $farmers,
            'search' => $search,
            'status' => $status,
            'totalFarmersCount' => $totalFarmersCount,
            'activeFarmersCount' => $activeFarmersCount,
            'inactiveFarmersCount' => $inactiveFarmersCount,
        ]);
    }

    /**
     * Show the form for creating a new farmer.
     */
    public function create(): View
    {
        return view('farmers.create', [
            'activeTab' => 'farmers',
            'title' => 'নতুন কৃষক যোগ করুন',
            'nextFarmerCode' => Farmer::generateNextFarmerCode(),
            'locations' => FarmLocation::where('status', 'active')->orderBy('name')->get(),
            'taskTemplates' => TaskTemplate::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created farmer in storage within a transaction.
     */
    public function store(StoreFarmerRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $farmer = DB::transaction(function () use ($request, $validated) {
            $farmerCode = Farmer::generateNextFarmerCode();

            $photoPath = null;
            if ($request->hasFile('profile_photo')) {
                $photoPath = $request->file('profile_photo')->store('farmers/photos', 'public');
            }

            $userId = null;
            $appAccess = $request->boolean('app_access_enabled', true);
            $plainPassword = $request->input('password') ?: '123456';

            if ($appAccess) {
                $user = User::where('phone', $validated['phone'])->first();
                if ($user) {
                    $user->update([
                        'name' => $validated['full_name'],
                        'email' => $validated['email'] ?? $user->email,
                        'password' => $plainPassword,
                        'role' => 'farmer',
                        'is_active' => true,
                    ]);
                } else {
                    $user = User::create([
                        'name' => $validated['full_name'],
                        'phone' => $validated['phone'],
                        'email' => $validated['email'] ?? null,
                        'password' => $plainPassword,
                        'role' => 'farmer',
                        'is_active' => true,
                    ]);
                }
                $userId = $user->id;
            }

            $status = $request->input('action') === 'draft' ? 'inactive' : ($validated['status'] ?? 'active');

            $farmer = Farmer::create([
                'user_id' => $userId,
                'farmer_code' => $farmerCode,
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'],
                'nid' => $validated['nid'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? 'পুরুষ',
                'address' => $validated['address'],
                'primary_activity' => $validated['primary_activity'],
                'experience_years' => $validated['experience_years'] ?? null,
                'profile_photo_path' => $photoPath,
                'app_access_enabled' => $appAccess,
                'status' => $status,
                'created_by' => auth()->id(),
            ]);

            // Sync Primary Farm Location if selected
            if (! empty($validated['farm_location_id'])) {
                $farmer->farmLocations()->sync([$validated['farm_location_id']]);
            }

            // Assign initial task if selected
            if (! empty($validated['initial_task_id']) && ! empty($validated['farm_location_id'])) {
                TaskAssignment::create([
                    'task_template_id' => $validated['initial_task_id'],
                    'farmer_id' => $farmer->id,
                    'farm_location_id' => $validated['farm_location_id'],
                    'scheduled_date' => now()->toDateString(),
                    'start_time' => '08:00:00',
                    'end_time' => '17:00:00',
                    'priority' => 'normal',
                    'status' => 'pending',
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                ]);
            }

            $farmer->emergencyContact()->create([
                'name' => $validated['emergency_contact_name'],
                'phone' => $validated['emergency_contact_phone'],
                'relationship' => $validated['emergency_contact_relationship'],
            ]);

            ActivityLog::record(
                action: 'farmer.created',
                description: "নতুন কৃষক যোগ করা হয়েছে: {$farmer->full_name} ({$farmer->farmer_code})",
                subject: $farmer,
                newValues: $farmer->toArray()
            );

            NotificationService::notifyAdmins(new FarmerCreatedNotification($farmer));

            return $farmer;
        });

        DashboardService::clearKpiCache();

        return redirect()->route('admin.farmers.index')->with('success', "কৃষক {$farmer->full_name} ({$farmer->farmer_code}) সফলভাবে সংরক্ষিত হয়েছে। মোবাইল অ্যাপে লগইনের জন্য পাসওয়ার্ড সক্রিয় করা হয়েছে।");
    }

    /**
     * Display the specified farmer.
     */
    public function show(Farmer $farmer): View
    {
        $farmer->load([
            'emergencyContact',
            'farmLocations',
            'taskAssignments' => function ($query) {
                $query->with(['taskTemplate', 'farmLocation'])->latest()->limit(10);
            },
        ]);

        return view('farmers.show', [
            'activeTab' => 'farmers',
            'title' => "কৃষক বিবরণ — {$farmer->full_name}",
            'farmer' => $farmer,
        ]);
    }

    /**
     * Show the form for editing the specified farmer.
     */
    public function edit(Farmer $farmer): View
    {
        $farmer->load(['emergencyContact', 'farmLocations', 'user']);

        return view('farmers.edit', [
            'activeTab' => 'farmers',
            'title' => "কৃষক তথ্য সম্পাদনা — {$farmer->full_name}",
            'farmer' => $farmer,
            'locations' => FarmLocation::where('status', 'active')->orderBy('name')->get(),
            'taskTemplates' => TaskTemplate::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified farmer in storage within a transaction.
     */
    public function update(UpdateFarmerRequest $request, Farmer $farmer): RedirectResponse
    {
        $validated = $request->validated();
        $oldValues = $farmer->toArray();

        DB::transaction(function () use ($request, $farmer, $validated, $oldValues) {
            if ($request->hasFile('profile_photo')) {
                if ($farmer->profile_photo_path && Storage::disk('public')->exists($farmer->profile_photo_path)) {
                    Storage::disk('public')->delete($farmer->profile_photo_path);
                }
                $farmer->profile_photo_path = $request->file('profile_photo')->store('farmers/photos', 'public');
            }

            $isDeactivated = ($farmer->status === 'active' && $validated['status'] === 'inactive');
            $appAccess = $request->boolean('app_access_enabled', true);

            // Sync linked user
            if ($appAccess) {
                $user = $farmer->user ?? User::where('phone', $validated['phone'])->first();
                $userData = [
                    'name' => $validated['full_name'],
                    'phone' => $validated['phone'],
                    'email' => $validated['email'] ?? ($user?->email),
                    'role' => 'farmer',
                    'is_active' => ($validated['status'] === 'active'),
                ];
                if ($request->filled('password')) {
                    $userData['password'] = $request->input('password');
                }
                if ($user) {
                    $user->update($userData);
                } else {
                    $userData['password'] = $request->input('password', '123456');
                    $user = User::create($userData);
                }
                $farmer->user_id = $user->id;
            } else {
                if ($farmer->user) {
                    $farmer->user->update(['is_active' => false]);
                }
            }

            $farmer->fill([
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'],
                'nid' => $validated['nid'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? 'পুরুষ',
                'address' => $validated['address'],
                'primary_activity' => $validated['primary_activity'],
                'experience_years' => $validated['experience_years'] ?? null,
                'app_access_enabled' => $appAccess,
                'status' => $validated['status'],
            ]);
            $farmer->save();

            if ($request->has('farm_location_id')) {
                if ($request->filled('farm_location_id')) {
                    $farmer->farmLocations()->sync([$request->input('farm_location_id')]);
                } else {
                    $farmer->farmLocations()->detach();
                }
            }

            $farmer->emergencyContact()->updateOrCreate(
                ['farmer_id' => $farmer->id],
                [
                    'name' => $validated['emergency_contact_name'],
                    'phone' => $validated['emergency_contact_phone'],
                    'relationship' => $validated['emergency_contact_relationship'],
                ]
            );

            $action = $isDeactivated ? 'farmer.deactivated' : 'farmer.updated';
            $description = $isDeactivated
                ? "কৃষক নিষ্ক্রিয় করা হয়েছে: {$farmer->full_name} ({$farmer->farmer_code})"
                : "কৃষকের তথ্য হালনাগাদ করা হয়েছে: {$farmer->full_name} ({$farmer->farmer_code})";

            ActivityLog::record(
                action: $action,
                description: $description,
                subject: $farmer,
                oldValues: $oldValues,
                newValues: $farmer->toArray()
            );
        });

        DashboardService::clearKpiCache();

        return redirect()->route('admin.farmers.index')->with('success', "কৃষক {$farmer->full_name}-এর তথ্য সফলভাবে আপডেট করা হয়েছে।");
    }

    /**
     * Remove the specified farmer from storage permanently.
     */
    public function destroy(Farmer $farmer): RedirectResponse
    {
        $farmerName = $farmer->full_name;
        $farmerCode = $farmer->farmer_code;
        $farmerId = $farmer->id;
        $userId = $farmer->user_id;

        DB::transaction(function () use ($farmer, $farmerName, $farmerCode, $farmerId, $userId) {
            $oldValues = $farmer->toArray();

            // 1. Delete profile photo from storage if exists
            if ($farmer->profile_photo_path && Storage::disk('public')->exists($farmer->profile_photo_path)) {
                Storage::disk('public')->delete($farmer->profile_photo_path);
            }

            // 2. Clean up task submissions evidence photos and assignment notifications
            foreach ($farmer->taskAssignments as $assignment) {
                if ($assignment->submission) {
                    foreach ($assignment->submission->evidencePhotos as $photo) {
                        if ($photo->photo_path && Storage::disk('public')->exists($photo->photo_path)) {
                            Storage::disk('public')->delete($photo->photo_path);
                        }
                    }
                }
                DB::table('notifications')
                    ->where('data', 'like', '%"assignment_id":'.$assignment->id.'%')
                    ->orWhere('data', 'like', '%"assignment_id":'.'"'.$assignment->id.'"'.'%')
                    ->delete();
            }

            // 3. Detach farm locations
            $farmer->farmLocations()->detach();

            // 4. Unset farmer_id on farm_locations if this farmer is the owner
            FarmLocation::where('farmer_id', $farmerId)->update(['farmer_id' => null]);

            // 5. Delete emergency contact
            $farmer->emergencyContact()->delete();

            // 6. Delete linked user account and its tokens/notifications
            if ($userId) {
                $user = User::find($userId);
                if ($user && $user->role === 'farmer') {
                    DB::table('notifications')
                        ->where('notifiable_type', User::class)
                        ->where('notifiable_id', $userId)
                        ->delete();

                    $user->tokens()->delete();
                    $user->delete();
                }
            }

            // Also clean up any user row having this phone number if role is farmer
            if ($farmer->phone) {
                $phoneUser = User::where('phone', $farmer->phone)->where('role', 'farmer')->first();
                if ($phoneUser && $phoneUser->id !== $userId) {
                    $phoneUser->tokens()->delete();
                    $phoneUser->delete();
                }
            }

            // 7. Record activity log before deleting
            ActivityLog::record(
                action: 'farmer.deleted',
                description: "কৃষক স্থায়ীভাবে মুছে ফেলা হয়েছে: {$farmerName} ({$farmerCode})",
                subject: $farmer,
                oldValues: $oldValues
            );

            // 8. Permanently delete the farmer
            $farmer->forceDelete();
        });

        DashboardService::clearKpiCache();

        return redirect()->route('admin.farmers.index')->with('success', "কৃষক {$farmerName} ({$farmerCode}) সফলভাবে ডাটাবেজ থেকে স্থায়ীভাবে মুছে ফেলা হয়েছে।");
    }
}
