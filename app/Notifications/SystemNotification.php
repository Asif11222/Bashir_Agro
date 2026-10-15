<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    public ?string $link;

    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly string $module = 'system',
        ?string $link = null,
        ?string $actionUrl = null,
        public readonly ?array $additionalInfo = null
    ) {
        $this->link = $link ?? $actionUrl;
    }

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
        return [
            'title' => $this->title,
            'message' => $this->message,
            'module' => $this->module,
            'link' => $this->link,
            'action_url' => $this->link,
            'timestamp' => now()->toIso8601String(),
            'additional_information' => $this->additionalInfo,
        ];
    }
}
