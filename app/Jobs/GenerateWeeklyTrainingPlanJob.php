<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\DailyRecommendation;
use App\Models\Objective;
use App\Models\User;
use App\Notifications\DailyTrainingPlanNotification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class GenerateWeeklyTrainingPlanJob implements ShouldBeUnique, ShouldQueue
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
        return (string) $this->user->id;
    }

    /**
     * Create a new job instance.
     *
     * @param  User  $user  The user for whom to generate the training plan
     * @param  Objective  $objective  The user's active objective
     * @param  bool  $force  Force regeneration even if plans already exist for the next 7 days
     * @param  bool  $sendNotification  Whether to send an email notification for today's recommendation
     */
    public function __construct(
        public User $user,
        public Objective $objective,
        public bool $force = false,
        public bool $sendNotification = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if objective is still active before processing
        if ($this->objective->status !== 'active') {
            Log::info("Objective {$this->objective->id} is no longer active. Skipping training plan generation.");

            return;
        }

        // Check if objective has expired
        if ($this->objective->target_date && $this->objective->target_date->isPast()) {
            Log::info("Objective {$this->objective->id} has expired (target date: {$this->objective->target_date->toDateString()}). Marking as completed and skipping.");
            $this->objective->update(['status' => 'completed']);

            return;
        }

        $startDate = now()->toDateString();
        $endDate = now()->addDays(6)->toDateString();

        if (! $this->force && DailyRecommendation::where('user_id', $this->user->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->count() >= 7) {
            Log::info("Daily training plans for the next 7 days already exist for user: {$this->user->id}. Skipping.");

            return;
        }

        Log::info("Generating 7-day training plan for user: {$this->user->id} on objective: {$this->objective->id}");

        try {
            $historicalContext = $this->getHistoricalContext();
            $recommendationContext = $this->getRecommendationContext();
            $planVsActualContext = $this->getPlanVsActualContext();
            $objectiveInfo = $this->getObjectiveInfo();
            $today = now()->format('l, Y-m-d');
            $locale = $this->user->preferredLocale();
            $language = $locale === 'nl' ? 'Dutch' : 'English';

            $basePrompt = "Objective:\n{$objectiveInfo}\n\n"
                ."Training History (Last 6 weeks):\n{$historicalContext}\n\n"
                ."Plan vs Actual (Last 14 days — compare what was planned with what was actually done):\n{$planVsActualContext}\n\n"
                ."Last 3 Recommendations:\n{$recommendationContext}\n\n"
                ."Today is {$today}.\n\n";

            if ($this->objective->enhancement_prompt) {
                $basePrompt .= "Additional Enhancement Instructions:\n{$this->objective->enhancement_prompt}\n\n";
            }

            $prompt = $basePrompt
                ."Your task has TWO parts, both in {$language}.\n\n"
                ."PART 1 — Adherence feedback:\n"
                ."Review the 'Plan vs Actual' section above. Write an honest, critical assessment of the athlete's adherence to the plan over the last 14 days. Be specific: name missed sessions, workouts that deviated in type/distance/intensity, and unplanned activities that replaced planned ones. If they skipped a session, call it out. If they ran when a rest day was planned, address the recovery implications. If pace or distance materially differed from prescription, point it out. Balance this with genuine recognition where adherence was solid — but do NOT sugarcoat. This athlete hired a coach, not a cheerleader. Keep it concise (2–5 sentences) and actionable. If there is nothing meaningful to flag, say so plainly.\n\n"
                ."PART 2 — Training plan for the upcoming 7 days, starting from today ({$today}):\n"
                .'Think carefully about the best progression toward the objective. Consider accumulated fatigue, adherence to past sessions (reduce load if many were missed; reinforce consistency if adherence was poor), recovery, periodization, and the target date ('.$this->objective->target_date->toDateString().'). '
                ."Prioritize long-term development over short-term feel-good sessions — a plan that respects recovery and progressive overload beats one that looks impressive on paper. Honor the athlete's preferred running days; on other days prescribe Rest or cross-training as appropriate. For each day, provide the type of run, a short title, a detailed description (distance/time and pace where relevant), and reasoning that ties the session to the objective and recent training context. All text fields must be in {$language}.";

            $schema = new ObjectSchema(
                name: 'training_plan_wrapper',
                description: 'A wrapper for the coach feedback and training plan',
                properties: [
                    new ArraySchema(
                        name: 'training_plan',
                        description: "A list of 7 daily training plans for a runner starting from today, written in {$language}",
                        items: new ObjectSchema(
                            name: 'daily_plan',
                            description: "A structured daily training plan in {$language}",
                            properties: [
                                new StringSchema('date', 'The date of the workout (YYYY-MM-DD)'),
                                new StringSchema('type', "The type of run in {$language} (e.g., Easy Run, Intervals, Long Run, Rest)"),
                                new StringSchema('title', "A short catchy title for the workout in {$language}"),
                                new StringSchema('description', "Detailed instructions for the workout including distance/time and pace if applicable in {$language}"),
                                new StringSchema('reasoning', "Explain why this specific workout is recommended for this day based on history, adherence, and objective in {$language}"),
                            ],
                            requiredFields: ['date', 'type', 'title', 'description', 'reasoning']
                        ),
                        minItems: 7,
                        maxItems: 7
                    ),
                    new StringSchema(
                        'adherence_feedback',
                        "Critical, honest coaching feedback in {$language} on the athlete's adherence to the plan over the last 14 days. Explicitly mention missed sessions, deviations between planned and actual activities, and unplanned training. Balanced but not sugarcoated. Use an empty string only if there is no prior plan history to evaluate."
                    ),
                ],
                requiredFields: ['training_plan', 'adherence_feedback']
            );

            $response = Prism::structured()
                ->using(Provider::OpenAI, 'gpt-5')
                ->withSchema($schema)
                ->withProviderOptions([
                    'reasoning' => ['effort' => 'high'],
                ])
                ->withSystemPrompt(
                    "You are Lararun's expert running coach in {$language}. You are honest, evidence-based, and long-term oriented. You are NOT a cheerleader — when the athlete misses sessions, deviates from the plan, or trains inappropriately, you call it out directly and constructively. You balance critique with recognition when it's earned. You reason carefully about periodization, adherence, and recovery before producing any plan. Always compare planned vs actual activities before recommending the next steps."
                )
                ->withPrompt($prompt)
                ->asStructured();

            $adherenceFeedback = is_string($response->structured['adherence_feedback'] ?? null)
                ? trim($response->structured['adherence_feedback'])
                : '';
            $dailyPlans = $response->structured['training_plan'];

            foreach ($dailyPlans as $planData) {
                $recommendation = DailyRecommendation::where('user_id', $this->user->id)
                    ->whereDate('date', $planData['date'])
                    ->first();

                $attributes = [
                    'objective_id' => $this->objective->id,
                    'type' => $planData['type'],
                    'title' => $planData['title'],
                    'description' => $planData['description'],
                    'reasoning' => $planData['reasoning'],
                ];

                // Store adherence feedback only on today's recommendation — it's a
                // single retrospective assessment, not a per-day value.
                if ($planData['date'] === $startDate) {
                    $attributes['adherence_feedback'] = $adherenceFeedback !== '' ? $adherenceFeedback : null;
                }

                if ($recommendation) {
                    $recommendation->update($attributes);
                } else {
                    $recommendation = DailyRecommendation::create([
                        'user_id' => $this->user->id,
                        'date' => $planData['date'],
                        ...$attributes,
                    ]);
                }

                if ($this->sendNotification && $planData['date'] === $startDate) {
                    // Double-check objective is still active before sending notification
                    $this->objective->refresh();

                    if ($this->objective->status !== 'active') {
                        Log::info("Skipping notification for user {$this->user->id} - objective no longer active");
                    } elseif (! $this->isTodayARunningDay()) {
                        Log::info("Skipping notification for user {$this->user->id} - today is not a preferred running day");
                    } else {
                        $this->user->notify(new DailyTrainingPlanNotification($recommendation));
                    }
                }
            }

            $logMessage = "7-day training plan generated for user: {$this->user->id}";
            if ($this->sendNotification) {
                $logMessage .= ' and notification sent';
            }
            Log::info($logMessage);
        } catch (\Exception $e) {
            Log::error("Failed to generate training plan for user {$this->user->id}: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * Determine whether today falls within the objective's preferred running days.
     *
     * If no running days are configured, every day is treated as a running day
     * to preserve existing behavior for objectives without a schedule.
     */
    protected function isTodayARunningDay(): bool
    {
        $runningDays = $this->objective->running_days;

        if (! is_array($runningDays) || empty($runningDays)) {
            return true;
        }

        return in_array(now()->format('l'), $runningDays, true);
    }

    /**
     * Get objective information for the prompt.
     */
    protected function getObjectiveInfo(): string
    {
        $runningDays = is_array($this->objective->running_days)
            ? implode(', ', $this->objective->running_days)
            : 'Not specified';

        return "Type: {$this->objective->type}\n"
            ."Target Date: {$this->objective->target_date?->toDateString()}\n"
            ."Description: {$this->objective->description}\n"
            ."Preferred Running Days: {$runningDays}";
    }

    /**
     * Get historical context (last 6 weeks of activities, with weekly summary).
     */
    protected function getHistoricalContext(): string
    {
        $historicalActivities = Activity::where('user_id', $this->user->id)
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
            $activities .= "Date: {$activity->start_date?->toDateString()} ({$activity->start_date?->format('l')})\n";
            $activities .= "Type: {$activity->type}\n";
            $activities .= 'Distance: '.round($activity->distance / 1000, 2)." km\n";
            $activities .= 'Duration: '.$this->formatDuration($activity->moving_time)."\n";
            $activities .= 'Pace: '.$this->formatPace($activity->distance, $activity->moving_time)."\n";
            $activities .= 'Intensity Score: '.($activity->intensity_score ?? 'n/a')."\n";

            if ($activity->zone_data_available) {
                $activities .= 'Zones (min): '
                    .'Z1='.$this->secondsToMinutes($activity->z1_time)
                    .' Z2='.$this->secondsToMinutes($activity->z2_time)
                    .' Z3='.$this->secondsToMinutes($activity->z3_time)
                    .' Z4='.$this->secondsToMinutes($activity->z4_time)
                    .' Z5='.$this->secondsToMinutes($activity->z5_time)."\n";
            }

            if ($activity->short_evaluation) {
                $activities .= "Coach's Evaluation: {$activity->short_evaluation}\n";
            }
        }

        return "Weekly Summary (most recent first):\n{$weeklySummary}\n"
            ."Individual Activities:\n{$activities}";
    }

    /**
     * Build a per-week summary of training load.
     *
     * @param  \Illuminate\Support\Collection<int, Activity>  $activities
     */
    protected function buildWeeklySummary(\Illuminate\Support\Collection $activities): string
    {
        $groups = $activities->groupBy(fn (Activity $a): string => $a->start_date?->startOfWeek()->toDateString() ?? 'unknown');

        $lines = '';
        foreach ($groups as $weekStart => $weekActivities) {
            $totalDistanceKm = round($weekActivities->sum('distance') / 1000, 1);
            $count = $weekActivities->count();
            $avgIntensity = $weekActivities->whereNotNull('intensity_score')->avg('intensity_score');
            $longestKm = round($weekActivities->max('distance') / 1000, 1);
            $weekEnd = \Illuminate\Support\Carbon::parse($weekStart)->endOfWeek()->toDateString();

            $lines .= "Week {$weekStart} → {$weekEnd}: "
                ."{$count} runs, {$totalDistanceKm} km total, longest {$longestKm} km, "
                .'avg intensity '.($avgIntensity !== null ? round($avgIntensity, 2) : 'n/a')."\n";
        }

        return $lines;
    }

    protected function formatDuration(?int $seconds): string
    {
        if (! $seconds) {
            return 'n/a';
        }

        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;

        return sprintf('%d:%02d min', $minutes, $remaining);
    }

    protected function formatPace(?float $distanceMeters, ?int $movingTimeSeconds): string
    {
        if (! $distanceMeters || ! $movingTimeSeconds) {
            return 'n/a';
        }

        $secondsPerKm = $movingTimeSeconds / ($distanceMeters / 1000);
        $minutes = intdiv((int) $secondsPerKm, 60);
        $remaining = (int) round($secondsPerKm - ($minutes * 60));

        return sprintf('%d:%02d min/km', $minutes, $remaining);
    }

    protected function secondsToMinutes(?int $seconds): int
    {
        return $seconds ? (int) round($seconds / 60) : 0;
    }

    /**
     * Build a plan-vs-actual comparison for the last 14 days.
     *
     * For each date in the window, match any prior recommendation with the activities
     * logged that day. This gives the coach a factual basis for adherence feedback.
     */
    protected function getPlanVsActualContext(): string
    {
        $windowStart = now()->subDays(14)->startOfDay();
        $windowEnd = now()->subDay()->endOfDay();

        $recommendations = DailyRecommendation::where('user_id', $this->user->id)
            ->whereBetween('date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->get()
            ->keyBy(fn (DailyRecommendation $r): string => $r->date?->toDateString() ?? '');

        $activities = Activity::where('user_id', $this->user->id)
            ->whereBetween('start_date', [$windowStart, $windowEnd])
            ->orderBy('start_date')
            ->get()
            ->groupBy(fn (Activity $a): string => $a->start_date?->toDateString() ?? '');

        if ($recommendations->isEmpty() && $activities->isEmpty()) {
            return 'No planned sessions or activities recorded in the last 14 days.';
        }

        $lines = '';
        for ($i = 14; $i >= 1; $i--) {
            $date = now()->subDays($i)->toDateString();
            $dayName = now()->subDays($i)->format('l');
            $rec = $recommendations->get($date);
            $dayActivities = $activities->get($date, collect());

            $plannedLabel = $rec
                ? "PLANNED: {$rec->type}"
                : 'PLANNED: (no prior plan for this day)';

            if ($dayActivities->isEmpty()) {
                $actualLabel = 'ACTUAL: no activity recorded';
            } else {
                $parts = [];
                foreach ($dayActivities as $activity) {
                    $distanceKm = round($activity->distance / 1000, 2);
                    $pace = $this->formatPace($activity->distance, $activity->moving_time);
                    $parts[] = "{$activity->type} {$distanceKm} km @ {$pace} (intensity ".($activity->intensity_score ?? 'n/a').')';
                }
                $actualLabel = 'ACTUAL: '.implode('; ', $parts);
            }

            $lines .= "{$date} ({$dayName})\n  {$plannedLabel}\n  {$actualLabel}\n";
        }

        return $lines;
    }

    /**
     * Get recommendation context (last 3 recommendations).
     */
    protected function getRecommendationContext(): string
    {
        $lastRecommendations = DailyRecommendation::where('user_id', $this->user->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        if ($lastRecommendations->isEmpty()) {
            return 'No previous recommendations.';
        }

        $context = '';
        foreach ($lastRecommendations as $recommendation) {
            $context .= "--- Recommendation ---\n";
            $context .= "Date: {$recommendation->date?->toDateString()}\n";
            $context .= "Type: {$recommendation->type}\n";
            $context .= "Title: {$recommendation->title}\n";
            $context .= "Description: {$recommendation->description}\n";
        }

        return $context;
    }
}
