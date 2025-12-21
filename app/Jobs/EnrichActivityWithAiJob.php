<?php

namespace App\Jobs;

use App\Models\Activity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;

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

            // Short evaluation
            $shortResponse = Prism::text()
                ->using('openai', 'gpt-4o')
                ->withSystemPrompt("You are an expert running coach. Provide a very brief (max 2 sentences) encouraging evaluation of this run. Consider the user's recent history to provide more personalized feedback.")
                ->withPrompt("Recent History (Last 30 days):\n{$historicalContext}\n\nCurrent Activity:\n{$activityData}")
                ->asText();

            $this->activity->update([
                'short_evaluation' => trim($shortResponse->text),
            ]);

            Log::debug("Short evaluation generated. Tokens: {$shortResponse->usage->promptTokens} prompt, {$shortResponse->usage->completionTokens} completion");

            // Extended evaluation
            $extendedResponse = Prism::text()
                ->using('openai', 'gpt-4o')
                ->withSystemPrompt("You are an expert running coach. Provide a detailed analysis of this run, considering distance, pace, and heart rate zones. Give specific advice for improvement or recovery. Use the user's history from the last month to identify trends or changes in performance.")
                ->withPrompt("Recent History (Last 30 days):\n{$historicalContext}\n\nCurrent Activity:\n{$activityData}")
                ->asText();

            $this->activity->update([
                'extended_evaluation' => trim($extendedResponse->text),
            ]);

            Log::debug("Extended evaluation generated. Tokens: {$extendedResponse->usage->promptTokens} prompt, {$extendedResponse->usage->completionTokens} completion");

            Log::info("Finished AI enrichment for activity: {$this->activity->id}");
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
