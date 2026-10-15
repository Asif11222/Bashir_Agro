<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications for the authenticated user.
     */
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all');
        $user = auth()->user();

        $query = $user->notifications();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->paginate(20)->withQueryString();
        $unreadCount = $user->unreadNotifications()->count();
        $totalCount = $user->notifications()->count();

        return view('notifications.index', [
            'activeTab' => 'notifications',
            'title' => 'সিস্টেম নোটিফিকেশন',
            'notifications' => $notifications,
            'filter' => $filter,
            'unreadCount' => $unreadCount,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(string $id): RedirectResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return back()->with('success', 'নোটিফিকেশন পঠিত হিসেবে চিহ্নিত করা হয়েছে।');
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'সকল নোটিফিকেশন পঠিত হিসেবে চিহ্নিত করা হয়েছে।');
    }

    /**
     * Remove the specified notification from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->delete();

        return back()->with('success', 'নোটিফিকেশন মুছে ফেলা হয়েছে।');
    }
}
