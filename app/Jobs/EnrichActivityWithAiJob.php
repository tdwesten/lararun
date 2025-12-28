<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Notifications\ActivityEvaluatedNotification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class EnrichActivityWithAiJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->activity->id;
    }

    /**
     * Create a new job instance.
     */
    public function __construct(public Activity $activity, public bool $sendNotification = true) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting AI enrichment for activity: {$this->activity->id} (Strava ID: {$this->activity->strava_id})");

        try {
            $activityData = $this->getActivitySummary();
            $historicalContext = $this->getHistoricalContext();
            $locale = $this->activity->user->preferredLocale();
            $language = $locale === 'nl' ? 'Dutch' : 'English';

            $schema = new ObjectSchema(
                name: 'activity_evaluation',
                description: 'Structured evaluation of a running activity',
                properties: [
                    new StringSchema('short_evaluation', "A very brief (max 2 sentences) encouraging evaluation of the run in {$language}."),
                    new StringSchema('extended_evaluation', "A detailed analysis of the run in {$language}, following a specific structure including Performance Analysis, Current Activity Overview, Historical Trends, Recommendations, and Additional Advice."),
                ],
                requiredFields: ['short_evaluation', 'extended_evaluation']
            );

            $systemPrompt = <<<PROMPT
You are an expert running coach. You provide both brief encouraging feedback and detailed technical analysis.
The 'short_evaluation' and 'extended_evaluation' MUST be written in {$language}.
The 'extended_evaluation' MUST follow this exact structure and formatting use Markdown:

# Performance Analysis

## Summary of Activity
[Provide a in-depth evaluation of the activity in 1-10 sentences.]

## Historical Trends (Last 10 Days)
[Provide 2-5 bullet points analyzing pacing improvement, heart rate consistency, and distance/duration trends compared to the provided history.]

## Recommendations for Improvement
[Provide 2-5 bullet points on balanced intensity, recovery focus, and progressive overload.]

## Additional Advice
[Provide 2-5 bullet points on listening to your body, consistency, and hydration/nutrition.]

End with a summary sentence. Use the user's history from the last month to identify trends or changes in performance.
PROMPT;

            $response = Prism::structured()
                ->using(Provider::OpenAI, 'gpt-4o')
                ->withSchema($schema)
                ->withSystemPrompt($systemPrompt)
                ->withPrompt("Recent History (Last 30 days):\n{$historicalContext}\n\nCurrent Activity:\n{$activityData}")
                ->asStructured();

            $this->activity->update([
                'short_evaluation' => trim($response->structured['short_evaluation']),
                'extended_evaluation' => trim($response->structured['extended_evaluation']),
            ]);

            Log::debug("Activity evaluations generated. Tokens: {$response->usage->promptTokens} prompt, {$response->usage->completionTokens} completion");

            // Send notification
            if ($this->sendNotification) {
                $this->activity->user->notify(new ActivityEvaluatedNotification($this->activity));
            }

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
