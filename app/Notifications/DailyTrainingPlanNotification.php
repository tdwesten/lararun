<?php

namespace App\Notifications;

use App\Models\DailyRecommendation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyTrainingPlanNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public DailyRecommendation $recommendation) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your training plan for today: {$this->recommendation->title}")
            ->markdown('mail.daily-training-plan', [
                'recommendation' => $this->recommendation,
                'name' => $notifiable->name,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'recommendation_id' => $this->recommendation->id,
            'title' => $this->recommendation->title,
            'type' => $this->recommendation->type,
        ];
    }
}
