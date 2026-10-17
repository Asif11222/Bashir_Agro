<?php

use App\Models\TaskAssignment;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:backfill-farmers', function () {
    $assignments = TaskAssignment::with(['farmer.user', 'taskTemplate', 'location'])->get();
    $count = 0;
    foreach ($assignments as $assignment) {
        $user = $assignment->farmer?->user;
        if (! $user) {
            continue;
        }

        $alreadyNotified = $user->notifications
            ->contains(fn ($n) => ($n->data['assignment_id'] ?? null) == $assignment->id);

        if (! $alreadyNotified) {
            $user->notify(new TaskAssignedNotification($assignment));
            $count++;
        }
    }
    $this->info("Backfilled {$count} notifications for farmers.");
})->purpose('Backfill missing task assignment notifications for farmer accounts');
