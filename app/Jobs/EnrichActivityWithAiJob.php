<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Notifications\ActivityEvaluatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class EnrichActivityWithAiJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Activity $activity) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting AI enrichment for activity: {$this->activity->id} (Strava ID: {$this->activity->strava_id})");

        try {
            $activityData = $this->getActivitySummary();
            $historicalContext = $this->getHistoricalContext();

            $schema = new ObjectSchema(
                name: 'activity_evaluation',
                description: 'Structured evaluation of a running activity',
                properties: [
                    new StringSchema('short_evaluation', 'A very brief (max 2 sentences) encouraging evaluation of the run.'),
                    new StringSchema('extended_evaluation', 'A detailed analysis of the run, considering distance, pace, and heart rate zones. Specific advice for improvement or recovery.'),
                ],
                requiredFields: ['short_evaluation', 'extended_evaluation']
            );

            $response = Prism::structured()
                ->using(Provider::OpenAI, 'gpt-4o')
                ->withSchema($schema)
                ->withSystemPrompt("You are an expert running coach. You provide both brief encouraging feedback and detailed technical analysis. Use the user's history from the last month to identify trends or changes in performance.")
                ->withPrompt("Recent History (Last 30 days):\n{$historicalContext}\n\nCurrent Activity:\n{$activityData}")
                ->asStructured();

            $this->activity->update([
                'short_evaluation' => trim($response->structured['short_evaluation']),
                'extended_evaluation' => trim($response->structured['extended_evaluation']),
            ]);

            Log::debug("Activity evaluations generated. Tokens: {$response->usage->promptTokens} prompt, {$response->usage->completionTokens} completion");

            // Send notification
            $this->activity->user->notify(new ActivityEvaluatedNotification($this->activity));

            Log::info("Finished AI enrichment and sent notification for activity: {$this->activity->id}");
        } catch (\Exception $e) {
            Log::error("Failed to enrich activity {$this->activity->id} with AI: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * Get a summary of the activity for the AI.
     */
    protected function getActivitySummary(?Activity $activity = null): string
    {
        $a = $activity ?? $this->activity;
        $summary = "Date: {$a->start_date->toDateTimeString()}\n";
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
     * Get historical context (last 30 days of activities).
     */
    protected function getHistoricalContext(): string
    {
        $historicalActivities = Activity::where('user_id', $this->activity->user_id)
            ->where('id', '!=', $this->activity->id)
            ->where('start_date', '>=', now()->subDays(30))
            ->orderByDesc('start_date')
            ->limit(10) // Limit to last 10 activities to avoid too large prompt
            ->get();

        if ($historicalActivities->isEmpty()) {
            return 'No previous activities in the last 30 days.';
        }

        $context = '';
        foreach ($historicalActivities as $activity) {
            $context .= "--- Activity ---\n";
            $context .= $this->getActivitySummary($activity);
        }

        return $context;
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
}
