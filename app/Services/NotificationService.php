<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationService
{
    /**
     * Dispatch notification to all active administrators.
     */
    public static function notifyAdmins(Notification $notification): void
    {
        $admins = User::where(function ($q) {
            $q->whereIn('role', ['admin', 'owner'])->orWhereNull('role');
        })->where('is_active', true)->get();

        if ($admins->isEmpty()) {
            $admins = User::where('is_active', true)->get();
        }

        if ($admins->isNotEmpty()) {
            NotificationFacade::send($admins, $notification);
        }
    }
}
