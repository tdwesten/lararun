<?php

namespace App\Notifications;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ActivityEvaluatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Activity $activity) {}

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
     *
     * Uses the extended evaluation produced by EnrichActivityWithAiJob directly.
     * That evaluation is already written by gpt-5 with full objective, planned,
     * and historical context — a second AI call just to reformat it would
     * degrade quality and add latency.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $locale = $notifiable->preferredLocale();

        $content = trim((string) $this->activity->extended_evaluation);

        if ($content === '') {
            $content = trim((string) $this->activity->short_evaluation);
        }

        return (new MailMessage)
            ->subject(Lang::get('Your run is ready for review: :name', ['name' => $this->activity->name], $locale))
            ->markdown('mail.activity-evaluated', [
                'content' => $content,
                'activity' => $this->activity,
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
            'activity_id' => $this->activity->id,
        ];
    }
}
