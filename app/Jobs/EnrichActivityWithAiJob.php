<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\DailyRecommendation;
use App\Models\Objective;
use App\Notifications\ActivityEvaluatedNotification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
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
            $objectiveInfo = $this->getObjectiveInfo();
            $plannedContext = $this->getPlannedSessionContext();
            $locale = $this->activity->user->preferredLocale();
            $language = $locale === 'nl' ? 'Dutch' : 'English';

            $schema = new ObjectSchema(
                name: 'activity_evaluation',
                description: 'Structured evaluation of a running activity',
                properties: [
                    new StringSchema('short_evaluation', "A brief (max 2 sentences) honest evaluation of the run in {$language}. Not a cheerleader line — a concise coach's verdict."),
                    new StringSchema('extended_evaluation', "A detailed analysis of the run in {$language}, following the specified Markdown structure. Honest, long-term oriented, critical where warranted."),
                ],
                requiredFields: ['short_evaluation', 'extended_evaluation']
            );

            $systemPrompt = <<<PROMPT
You are Lararun's expert running coach writing in {$language}. You are honest, evidence-based, and long-term oriented. You are NOT a cheerleader — when the run was sloppy, poorly paced, too hard, too easy, or deviated from what was planned, you say so directly and explain why. You balance critique with genuine recognition when the athlete did the right work. You always reason about the run in the context of:
  (1) the athlete's stated objective,
  (2) what was planned for today (if anything), and
  (3) the trend of the last 6 weeks.

The 'short_evaluation' and 'extended_evaluation' MUST be written in {$language}.

The 'extended_evaluation' MUST follow this exact Markdown structure:

# Performance Analysis

## Summary of Activity
[1–5 sentences: what actually happened on this run. Pace, duration, effort, heart-rate distribution if available. Be factual.]

## Plan vs Actual
[2–4 sentences comparing this run with what was planned for today (if a plan existed). If nothing was planned, say so. If the run deviated from the plan in intensity, distance, pace, or type, call it out. If the athlete followed the plan, acknowledge it — don't oversell it.]

## Historical Trends (Last 6 Weeks)
[2–5 bullet points analysing pace progression, heart-rate consistency, volume trend, recovery balance. Flag concerning patterns (e.g. too many Z4/Z5 sessions, declining pace, dropped volume).]

## Progress Toward Objective
[2–4 sentences: does this run move the athlete closer to their objective given the target date? What's the net effect on readiness? Be realistic.]

## Recommendations
[2–5 bullet points. Concrete, actionable. Prioritize long-term development over short-term feel-good advice.]

End with one honest summary sentence — not a generic pep talk.
PROMPT;

            $prompt = "Objective:\n{$objectiveInfo}\n\n"
                ."Planned for today:\n{$plannedContext}\n\n"
                ."Training History (Last 6 weeks):\n{$historicalContext}\n\n"
                ."Current Activity:\n{$activityData}";

            // Prism's default max_tokens (2048) is consumed by gpt-5 reasoning;
            // raise it so the structured output still fits.
            $response = Prism::structured()
                ->using(Provider::OpenAI, 'gpt-5')
                ->withSchema($schema)
                ->withMaxTokens(16000)
                ->withProviderOptions([
                    'reasoning' => ['effort' => 'medium'],
                ])
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($prompt)
                ->asStructured();

            $this->activity->update([
                'short_evaluation' => trim($response->structured['short_evaluation']),
                'extended_evaluation' => trim($response->structured['extended_evaluation']),
            ]);

            Log::debug("Activity evaluations generated. Tokens: {$response->usage->promptTokens} prompt, {$response->usage->completionTokens} completion");

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
        $summary = "Date: {$a->start_date?->toDateTimeString()} ({$a->start_date?->format('l')})\n";
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
     * Get the user's active objective info, if any.
     */
    protected function getObjectiveInfo(): string
    {
        $objective = Objective::where('user_id', $this->activity->user_id)
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('target_date')
                    ->orWhere('target_date', '>=', now());
            })
            ->latest('id')
            ->first();

        if (! $objective) {
            return 'No active objective set.';
        }

        $runningDays = is_array($objective->running_days) && $objective->running_days !== []
            ? implode(', ', $objective->running_days)
            : 'Not specified';

        return "Type: {$objective->type}\n"
            .'Target Date: '.($objective->target_date?->toDateString() ?? 'n/a')."\n"
            ."Description: {$objective->description}\n"
            ."Preferred Running Days: {$runningDays}";
    }

    /**
     * Get the planned session for this activity's date, if one existed.
     */
    protected function getPlannedSessionContext(): string
    {
        $date = $this->activity->start_date?->toDateString();

        if (! $date) {
            return 'No planning context — activity date unknown.';
        }

        $recommendation = DailyRecommendation::where('user_id', $this->activity->user_id)
            ->whereDate('date', $date)
            ->first();

        if (! $recommendation) {
            return 'No session was planned for this date — this was an unplanned activity.';
        }

        return "Type: {$recommendation->type}\n"
            ."Title: {$recommendation->title}\n"
            ."Description: {$recommendation->description}\n"
            ."Reasoning given at planning time: {$recommendation->reasoning}";
    }

    /**
     * Get historical context (last 6 weeks of activities, with weekly summary).
     */
    protected function getHistoricalContext(): string
    {
        $historicalActivities = Activity::where('user_id', $this->activity->user_id)
            ->where('id', '!=', $this->activity->id)
            ->where('start_date', '>=', now()->subDays(42))
            ->orderByDesc('start_date')
            ->get();

        if ($historicalActivities->isEmpty()) {
            return 'No previous activities in the last 6 weeks.';
        }

        $weeklySummary = $this->buildWeeklySummary($historicalActivities);

        $activities = '';
        foreach ($historicalActivities as $activity) {
            $activities .= "--- Activity ---\n";
            $activities .= $this->getActivitySummary($activity);
        }

        return "Weekly Summary (most recent first):\n{$weeklySummary}\n"
            ."Individual Activities:\n{$activities}";
    }

    /**
     * Build a per-week summary of training load.
     *
     * @param  Collection<int, Activity>  $activities
     */
    protected function buildWeeklySummary(Collection $activities): string
    {
        $groups = $activities->groupBy(fn (Activity $a): string => $a->start_date?->startOfWeek()->toDateString() ?? 'unknown');

        $lines = '';
        foreach ($groups as $weekStart => $weekActivities) {
            $totalKm = round($weekActivities->sum('distance') / 1000, 1);
            $count = $weekActivities->count();
            $avgIntensity = $weekActivities->whereNotNull('intensity_score')->avg('intensity_score');
            $longestKm = round($weekActivities->max('distance') / 1000, 1);
            $weekEnd = \Illuminate\Support\Carbon::parse($weekStart)->endOfWeek()->toDateString();

            $lines .= "Week {$weekStart} → {$weekEnd}: "
                ."{$count} runs, {$totalKm} km total, longest {$longestKm} km, "
                .'avg intensity '.($avgIntensity !== null ? round($avgIntensity, 2) : 'n/a')."\n";
        }

        return $lines;
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
