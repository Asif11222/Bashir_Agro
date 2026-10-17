<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    /**
     * Display a listing of notifications for the authenticated user/farmer.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->paginate($request->integer('per_page', 30));

        $data = $notifications->getCollection()->map(function ($notification) {
            $payload = is_array($notification->data)
                ? $notification->data
                : (json_decode($notification->data, true) ?? []);

            return [
                'id' => (string) $notification->id,
                'event_type' => $payload['event_type'] ?? 'newTask',
                'title' => $payload['title'] ?? 'বিজ্ঞপ্তি',
                'message' => $payload['message'] ?? '',
                'timestamp' => $payload['timestamp'] ?? $notification->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'assignment_id' => $payload['assignment_id'] ?? ($payload['additional_information']['assignment_id'] ?? null),
                'is_read' => $notification->read_at !== null,
                'additional_information' => $payload['additional_information'] ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'unread_count' => $user->unreadNotifications()->count(),
                'total' => $notifications->total(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'বিজ্ঞপ্তিটি পঠিত হিসেবে চিহ্নিত করা হয়েছে।',
            'data' => [
                'id' => (string) $notification->id,
                'is_read' => true,
            ],
            'meta' => [
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * Mark all unread notifications for the authenticated user as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'সকল বিজ্ঞপ্তি পঠিত হিসেবে চিহ্নিত করা হয়েছে।',
            'meta' => [
                'unread_count' => 0,
            ],
        ]);
    }
}
