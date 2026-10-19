<?php

namespace App\Notifications;

use App\Models\TaskAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskCancelledNotification extends Notification
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
        $taskName = $this->assignment->taskTemplate->name ?? 'কাজ';
        $locationName = $this->assignment->location->name ?? 'ফার্ম';
        $scheduledDate = $this->assignment->scheduled_date instanceof \DateTimeInterface
            ? $this->assignment->scheduled_date->toDateString()
            : (string) ($this->assignment->scheduled_date ?? now()->toDateString());

        return [
            'title' => 'কাজ বরাদ্দ বাতিল/প্রত্যাহার',
            'message' => "আপনার '{$taskName}' কাজটির বরাদ্দ প্রত্যাহার/মুছে ফেলা হয়েছে ({$locationName})। তারিখ: {$scheduledDate}।",
            'module' => 'assignment',
            'event_type' => 'cancellation',
            'assignment_id' => $this->assignment->id,
            'link' => null,
            'action_url' => null,
            'timestamp' => now()->toIso8601String(),
            'additional_information' => [
                'assignment_id' => $this->assignment->id,
                'task_name' => $taskName,
                'location_name' => $locationName,
                'scheduled_date' => $scheduledDate,
            ],
        ];
    }
}
