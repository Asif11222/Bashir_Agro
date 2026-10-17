<?php

namespace App\Notifications;

use App\Models\TaskAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
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
        $locationName = $this->assignment->location->name ?? 'ফার্ম';
        $scheduledDate = $this->assignment->scheduled_date instanceof \DateTimeInterface
            ? $this->assignment->scheduled_date->toDateString()
            : (string) ($this->assignment->scheduled_date ?? now()->toDateString());

        $isFarmer = method_exists($notifiable, 'isFarmer') && $notifiable->isFarmer()
            || (isset($notifiable->id) && $notifiable->id === $this->assignment->farmer?->user_id);

        $message = $isFarmer
            ? "আপনাকে '{$taskName}' কাজ বরাদ্দ করা হয়েছে ({$locationName})। তারিখ: {$scheduledDate}।"
            : "কৃষক {$farmerName}-কে {$taskName} কাজ বরাদ্দ করা হয়েছে ({$locationName})।";

        return [
            'title' => 'নতুন কাজ বরাদ্দ',
            'message' => $message,
            'module' => 'assignment',
            'event_type' => 'newTask',
            'assignment_id' => $this->assignment->id,
            'link' => route('admin.assignments.show', $this->assignment),
            'action_url' => route('admin.assignments.show', $this->assignment),
            'timestamp' => now()->toIso8601String(),
            'additional_information' => [
                'assignment_id' => $this->assignment->id,
                'priority' => $this->assignment->priority,
                'scheduled_date' => $scheduledDate,
            ],
        ];
    }
}
