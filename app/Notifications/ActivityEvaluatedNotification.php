<?php

namespace App\Notifications;

use App\Models\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Prism\Prism\Facades\Prism;

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
     */
    public function toMail(object $notifiable): MailMessage
    {
        $summary = $this->getActivitySummary();

        $response = Prism::text()
            ->using('openai', 'gpt-4o')
            ->withSystemPrompt("You are Lararun's expert running coach. Write a brief, personalized email body to the runner {$notifiable->name} about their recent activity.
Use a professional yet encouraging tone.
Strict Instructions:
1. Use Markdown for formatting.
2. Include a summary of the activity (distance, pace, duration).
3. Incorporate the coach's evaluation: '{$this->activity->short_evaluation}'.
4. Keep it concise (max 3-4 short paragraphs).
5. DO NOT include a 'Subject' line.
6. DO NOT include a greeting (e.g., 'Hi Name').
7. DO NOT include a sign-off (e.g., 'Best regards').
8. Address the runner as {$notifiable->name} if you must mention them.")
            ->withPrompt("Activity Summary:\n{$summary}\n\nCoach's Evaluation: {$this->activity->short_evaluation}")
            ->asText();

        return (new MailMessage)
            ->subject("Your run is ready for review: {$this->activity->name}")
            ->markdown('mail.activity-evaluated', [
                'content' => $response->text,
                'activity' => $this->activity,
                'name' => $notifiable->name,
            ]);
    }

    /**
     * Get a summary of the activity for the AI.
     */
    protected function getActivitySummary(): string
    {
        $a = $this->activity;
        $summary = "Date: {$a->start_date?->toDateTimeString()}\n";
        $summary .= "Activity Name: {$a->name}\n";
        $summary .= "Type: {$a->type}\n";
        $summary .= 'Distance: '.round($a->distance / 1000, 2)." km\n";
        $summary .= 'Moving Time: '.gmdate('H:i:s', $a->moving_time)."\n";
        $summary .= 'Average Pace: '.$this->calculatePace($a->distance, $a->moving_time)." min/km\n";

        if ($a->zone_data_available) {
            $summary .= "Heart Rate Zones (time in seconds):\n";
            $summary .= "- Zone 1 (Recovery): {$a->z1_time}s\n";
            $summary .= "- Zone 2 (Aerobic): {$a->z2_time}s\n";
            $summary .= "- Zone 3 (Tempo): {$a->z3_time}s\n";
            $summary .= "- Zone 4 (Threshold): {$a->z4_time}s\n";
            $summary .= "- Zone 5 (Anaerobic): {$a->z5_time}s\n";
            $summary .= "Intensity Score: {$a->intensity_score}\n";
        }

        return $summary;
    }

    /**
     * Calculate pace in min/km.
     */
    protected function calculatePace(float $distance, int $time): string
    {
        if ($distance <= 0) {
            return '0:00';
        }

        $paceInSeconds = $time / ($distance / 1000);
        $minutes = floor($paceInSeconds / 60);
        $seconds = round(fmod($paceInSeconds, 60));

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
