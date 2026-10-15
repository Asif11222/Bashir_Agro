<?php

namespace App\Notifications;

use App\Models\TaskAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssignmentCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TaskAssignment $assignment
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $farmerName = $this->assignment->farmer->full_name ?? 'কৃষক';
        $taskName = $this->assignment->taskTemplate->name ?? 'কাজ';

        return [
            'title' => 'কাজ সম্পন্ন হয়েছে',
            'message' => "কৃষক {$farmerName} সফলভাবে {$taskName} কাজটি সম্পন্ন করেছেন।",
            'module' => 'assignment',
            'link' => route('admin.assignments.show', $this->assignment),
            'action_url' => route('admin.assignments.show', $this->assignment),
            'timestamp' => now()->toIso8601String(),
            'additional_information' => [
                'assignment_id' => $this->assignment->id,
                'status' => 'completed',
            ],
        ];
    }
}
