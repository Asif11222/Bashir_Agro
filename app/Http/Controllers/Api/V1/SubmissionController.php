<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubmissionResource;
use App\Models\EvidencePhoto;
use App\Models\TaskAssignment;
use App\Models\TaskSubmission;
use App\Services\GeofenceService;
use App\Services\TaskAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'photos.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'override_geofence' => ['nullable'],
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

        $requiresGeofence = (bool) ($assignment->taskTemplate->requires_geofence_validation
            ?? $assignment->taskTemplate->require_geofence
            ?? true);

        $overrideGeofence = $request->boolean('override_geofence')
            || $request->input('override_geofence') === '1'
            || $request->input('override_geofence') === 1
            || filter_var($request->input('override_geofence'), FILTER_VALIDATE_BOOLEAN);

        // Strict Geofence / Boundary Check: reject if outside boundary/radius and required unless farmer confirmed override
        if ($requiresGeofence && ! $geofenceResult['is_within'] && ! $overrideGeofence) {
            $dist = $geofenceResult['distance_meters'];
            $radius = $geofenceResult['radius_meters'];
            $isPolygon = ! empty($geofenceResult['polygon_validated']);

            $errorMessage = $isPolygon
                ? "আপনি নির্ধারিত ফার্ম সীমানার বাইরে অবস্থান করছেন। (সীমানা হতে দূরত্ব: {$dist} মিটার)"
                : "আপনি নির্ধারিত ফার্ম লোকেশনের জিও-ফেন্স পরিধির বাইরে অবস্থান করছেন। (দূরত্ব: {$dist} মিটার, গ্রহণযোগ্য: {$radius} মিটার)";

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'geofence' => [
                    'passed' => false,
                    'distance_meters' => $dist,
                    'radius_meters' => $radius,
                    'polygon_boundary' => $isPolygon,
                ],
            ], 422);
        }

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

        $submission = DB::transaction(function () use ($validated, $assignment, $geofenceResult, $uploadedFiles, $user, $overrideGeofence) {
            $photoPaths = [];

            $farmerNote = $validated['notes'] ?? null;
            if ($overrideGeofence && ! $geofenceResult['is_within']) {
                $tag = '[মাঠের বাইরে থেকে জমা দেওয়া]';
                $farmerNote = $farmerNote ? "{$farmerNote} {$tag}" : $tag;
            }

            $taskSubmission = TaskSubmission::updateOrCreate(
                ['task_assignment_id' => $assignment->id],
                [
                    'farmer_id' => $assignment->farmer_id,
                    'submitted_at' => now(),
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'accuracy' => $validated['accuracy'] ?? null,
                    'geofence_passed' => $geofenceResult['is_within'],
                    'distance_meters' => $geofenceResult['distance_meters'],
                    'is_late' => $assignment->is_past_deadline,
                    'status' => 'submitted',
                    'verification_status' => $geofenceResult['is_within'] ? 'pending' : 'flagged',
                    'validation_status' => $geofenceResult['is_within'] ? 'pending' : 'flagged',
                    'notes' => $farmerNote,
                    'farmer_note' => $farmerNote,
                ]
            );

            // Store uploaded photos
            foreach ($uploadedFiles as $file) {
                $path = $file->store('submissions/photos', 'public');
                $photoPaths[] = $path;

                EvidencePhoto::create([
                    'task_submission_id' => $taskSubmission->id,
                    'file_path' => $path,
                    'source' => 'camera',
                    'captured_at' => now(),
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
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

        return response()->json([
            'success' => true,
            'message' => 'কাজের প্রমাণ ও প্রতিবেদন সফলভাবে জমা হয়েছে।',
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
}
