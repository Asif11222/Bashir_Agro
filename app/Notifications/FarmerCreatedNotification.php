<?php

namespace App\Notifications;

use App\Models\Farmer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FarmerCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Farmer $farmer
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
        return [
            'title' => 'নতুন কৃষক নিবন্ধিত',
            'message' => "কৃষক {$this->farmer->full_name} ({$this->farmer->farmer_code}) সিস্টেমে সফলভাবে নিবন্ধিত হয়েছেন।",
            'module' => 'farmer',
            'link' => route('admin.farmers.show', $this->farmer),
            'action_url' => route('admin.farmers.show', $this->farmer),
            'timestamp' => now()->toIso8601String(),
            'additional_information' => [
                'farmer_id' => $this->farmer->id,
                'farmer_code' => $this->farmer->farmer_code,
                'phone' => $this->farmer->phone,
            ],
        ];
    }
}
