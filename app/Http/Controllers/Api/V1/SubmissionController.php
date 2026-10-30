<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\SubmissionResource;
use App\Models\EvidencePhoto;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskSubmission;
use App\Models\TaskTemplate;
use App\Services\DashboardService;
use App\Services\GeofenceService;
use App\Services\TaskAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{
    public function __construct(
        protected GeofenceService $geofenceService,
        protected TaskAssignmentService $assignmentService
    ) {}

    /**
     * Submit task completion with notes, GPS coordinates, geofence validation, and evidence photos.
     */
    public function submit(Request $request, TaskAssignment $assignment): JsonResponse
    {
        $user = $request->user();

        // Check ownership if farmer
        if ($user->isFarmer() && $user->farmerProfile && $assignment->farmer_id !== $user->farmerProfile->id) {
            return response()->json([
                'success' => false,
                'message' => 'এই কাজ জমা দেওয়ার অনুমতি আপনার নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'override_geofence' => ['nullable'],
            'photo_metadata' => ['nullable'],
        ]);

        $assignment->load(['taskTemplate', 'location', 'farmer']);

        // Geofence / Polygon Boundary validation
        $accuracy = isset($validated['accuracy']) ? (float) $validated['accuracy'] : 0.0;
        $geofenceResult = $this->geofenceService->validate(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            $assignment->location,
            $accuracy
        );

        $isWithinGeofence = (bool) ($geofenceResult['is_within'] ?? true);
        $dist = (float) ($geofenceResult['distance_meters'] ?? 0.0);
        $radius = (float) ($geofenceResult['radius_meters'] ?? 0.0);
        $isPolygon = ! empty($geofenceResult['polygon_validated']);

        // Check required photo count
        $uploadedFiles = $request->file('photos', []);
        $minPhotos = (int) ($assignment->taskTemplate->min_photos_required
            ?? $assignment->taskTemplate->required_photo_count
            ?? 0);

        if ($minPhotos > 0 && count($uploadedFiles) < $minPhotos) {
            return response()->json([
                'success' => false,
                'message' => "এই কাজের জন্য কমপক্ষে {$minPhotos} টি প্রামাণ্য ছবি জমা দেওয়া আবশ্যক।",
            ], 422);
        }

        $submission = DB::transaction(function () use ($validated, $assignment, $geofenceResult, $isWithinGeofence, $dist, $uploadedFiles, $user, $request) {
            $photoPaths = [];

            $farmerNote = $validated['notes'] ?? null;
            if (! $isWithinGeofence) {
                $tag = '[মাঠের বাইরে থেকে জমা দেওয়া]';
                $farmerNote = $farmerNote ? "{$farmerNote} {$tag}" : $tag;
            }

            $isStartedLate = false;
            if ($assignment->start_time && $assignment->started_at && $assignment->scheduled_date) {
                try {
                    $scheduledStart = \Illuminate\Support\Carbon::parse($assignment->scheduled_date->format('Y-m-d').' '.$assignment->start_time);
                    $isStartedLate = $assignment->started_at->isAfter($scheduledStart);
                } catch (\Throwable) {}
            }
            $isFinishedLate = (bool) $assignment->is_past_deadline;
            $isSubmissionLate = $isStartedLate || $isFinishedLate;

            $taskSubmission = TaskSubmission::updateOrCreate(
                ['task_assignment_id' => $assignment->id],
                [
                    'farmer_id' => $assignment->farmer_id,
                    'submitted_at' => now(),
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'accuracy' => $validated['accuracy'] ?? null,
                    'geofence_passed' => $isWithinGeofence,
                    'distance_meters' => $dist,
                    'is_late' => $isSubmissionLate,
                    'status' => 'submitted',
                    'verification_status' => $isWithinGeofence ? 'pending' : 'flagged',
                    'validation_status' => $isWithinGeofence ? 'pending' : 'flagged',
                    'notes' => $farmerNote,
                    'farmer_note' => $farmerNote,
                ]
            );

            $photosMeta = [];
            if ($request->filled('photo_metadata')) {
                $rawMeta = $request->input('photo_metadata');
                if (is_string($rawMeta)) {
                    $decoded = json_decode($rawMeta, true);
                    if (is_array($decoded)) {
                        $photosMeta = $decoded;
                    }
                } elseif (is_array($rawMeta)) {
                    $photosMeta = $rawMeta;
                }
            }

            // Store uploaded photos with exact per-photo GPS coordinates
            foreach ($uploadedFiles as $index => $file) {
                $path = $file->store('submissions/photos', 'public');
                $photoPaths[] = $path;

                $meta = $photosMeta[$index] ?? [];
                $photoLat = isset($meta['latitude']) && is_numeric($meta['latitude'])
                    ? (float) $meta['latitude']
                    : (float) $validated['latitude'];
                $photoLng = isset($meta['longitude']) && is_numeric($meta['longitude'])
                    ? (float) $meta['longitude']
                    : (float) $validated['longitude'];
                $capturedAt = isset($meta['captured_at']) && ! empty($meta['captured_at'])
                    ? Carbon::parse($meta['captured_at'])
                    : now();

                EvidencePhoto::create([
                    'task_submission_id' => $taskSubmission->id,
                    'file_path' => $path,
                    'source' => 'camera',
                    'captured_at' => $capturedAt,
                    'latitude' => $photoLat,
                    'longitude' => $photoLng,
                ]);
            }

            if (! empty($photoPaths)) {
                $taskSubmission->photos = $photoPaths;
                $taskSubmission->save();
            }

            // Mark assignment as completed
            $this->assignmentService->updateStatus($assignment, 'completed', [], $user);

            return $taskSubmission;
        });

        $submission->load(['evidencePhotos', 'farmer', 'taskAssignment']);

        $message = $isWithinGeofence
            ? 'কাজের প্রমাণ ও প্রতিবেদন সফলভাবে জমা হয়েছে।'
            : ($isPolygon
                ? "কাজের প্রমাণ সফলভাবে জমা হয়েছে। (নির্ধারিত ফার্ম সীমানা হতে {$dist} মিটার বাইরে অবস্থান করায় সতর্কবার্তা সহ চিহ্নিত করা হয়েছে)"
                : "কাজের প্রমাণ সফলভাবে জমা হয়েছে। (নির্ধারিত ফার্ম লোকেশনের বাইরে অবস্থান করায় সতর্কবার্তা সহ চিহ্নিত করা হয়েছে)");

        return response()->json([
            'success' => true,
            'message' => $message,
            'geofence' => [
                'passed' => $isWithinGeofence,
                'distance_meters' => $dist,
                'radius_meters' => $radius,
                'polygon_boundary' => $isPolygon,
            ],
            'data' => new SubmissionResource($submission),
        ], 201);
    }

    /**
     * View submission details for a specific assignment.
     */
    public function show(TaskAssignment $assignment): JsonResponse
    {
        $submission = $assignment->submission;

        if (! $submission) {
            return response()->json([
                'success' => false,
                'message' => 'এই কাজের কোনো জমা রেকর্ড খুঁজে পাওয়া যায়নি।',
            ], 404);
        }

        $submission->load(['evidencePhotos', 'farmer', 'taskAssignment']);

        return response()->json([
            'success' => true,
            'data' => new SubmissionResource($submission),
        ]);
    }

    /**
     * Create and submit a farmer self-reported completed task (e.g. from voice or text input).
     */
    public function selfReport(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isFarmer() || ! $user->farmerProfile) {
            return response()->json([
                'success' => false,
                'message' => 'শুধুমাত্র কৃষকরা নতুন কাজ রিপোর্ট করতে পারবেন।',
            ], 403);
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'farm_location_id' => ['nullable', 'exists:farm_locations,id'],
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ], [
            'photos.required' => 'কাজের ছবি তোলা বাধ্যতামূলক। অনুগ্রহ করে অন্তত একটি ছবি তুলুন।',
            'photos.min' => 'কাজের ছবি তোলা বাধ্যতামূলক। অনুগ্রহ করে অন্তত একটি ছবি তুলুন।',
            'description.required' => 'কাজের বিবরণ লিখুন বা মুখে বলুন।',
        ]);

        $farmerId = $user->farmerProfile->id;

        // Dedicated Self-Reported Task Template (TK-SELF)
        $template = TaskTemplate::where('task_code', 'TK-SELF')->first()
            ?? TaskTemplate::where('name', 'LIKE', '%স্ব-প্রতিবেদিত%')->first();

        if (! $template) {
            $template = TaskTemplate::create([
                'task_code' => 'TK-SELF',
                'name' => 'স্ব-প্রতিবেদিত কাজ',
                'category' => 'মাঠ পর্যায়ের কাজ',
                'description' => 'কৃষক কর্তৃক মাঠ থেকে সরাসরি ছবি ও বিবরণ সহ রিপোর্টকৃত কাজ।',
                'instructions' => 'কাজের লাইভ ছবি ও বিস্তারিত বিবরণ প্রদান করুন।',
                'required_photo_count' => 1,
                'require_realtime_photo' => true,
                'allow_gallery_upload' => false,
                'require_geofence' => false,
                'recurrence_type' => 'once',
                'is_active' => true,
            ]);
        }

        $locationId = $validated['farm_location_id'] ?? null;
        if (! $locationId) {
            // If farm_location_id is not provided, fallback to first location if foreign key requires it
            $location = FarmLocation::first();
            $locationId = $location?->id;
        }

        $now = now();
        $lat = $validated['latitude'] ?? null;
        $lng = $validated['longitude'] ?? null;
        $accuracy = $validated['accuracy'] ?? null;

        $assignment = DB::transaction(function () use ($farmerId, $template, $locationId, $now, $validated, $lat, $lng, $accuracy, $request) {
            $taskAssignment = TaskAssignment::create([
                'task_template_id' => $template->id,
                'farmer_id' => $farmerId,
                'farm_location_id' => $locationId,
                'scheduled_date' => $now->toDateString(),
                'start_time' => $now->copy()->subMinutes(30)->format('H:i:s'),
                'end_time' => $now->format('H:i:s'),
                'priority' => 'normal',
                'notes' => $validated['description'],
                'status' => 'completed',
                'assigned_by' => null,
                'assigned_at' => $now,
                'started_at' => $now->copy()->subMinutes(30),
                'completed_at' => $now,
            ]);

            $taskSubmission = TaskSubmission::create([
                'task_assignment_id' => $taskAssignment->id,
                'farmer_id' => $farmerId,
                'submitted_at' => $now,
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy' => $accuracy,
                'geofence_passed' => $lat !== null ? true : null,
                'distance_meters' => 0,
                'is_late' => false,
                'status' => 'completed',
                'verification_status' => 'verified',
                'validation_status' => 'verified',
                'notes' => $validated['description'],
                'farmer_note' => $validated['description'],
            ]);

            $uploadedFiles = $request->file('photos', []);
            if (! empty($uploadedFiles)) {
                foreach ($uploadedFiles as $photo) {
                    $path = $photo->store('submissions/'.$taskSubmission->id, 'public');
                    EvidencePhoto::create([
                        'task_submission_id' => $taskSubmission->id,
                        'file_path' => $path,
                        'captured_at' => $now,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'accuracy' => $accuracy,
                        'mime_type' => $photo->getClientMimeType(),
                        'file_size' => $photo->getSize(),
                    ]);
                }
            }

            return $taskAssignment;
        });

        DashboardService::clearKpiCache();
        $assignment->load(['taskTemplate', 'location', 'farmer', 'submission.evidencePhotos']);

        return response()->json([
            'success' => true,
            'message' => 'কাজটি সফলভাবে সম্পন্ন হিসেবে যুক্ত করা হয়েছে।',
            'data' => new AssignmentResource($assignment),
        ], 201);
    }
}
