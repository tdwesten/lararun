<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\DailyRecommendation;
use App\Models\Objective;
use App\Models\User;
use App\Notifications\DailyTrainingPlanNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class GenerateDailyTrainingPlanJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public Objective $objective,
        public bool $force = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
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
            $objectiveInfo = $this->getObjectiveInfo();
            $today = now()->format('l, Y-m-d');

            $prompt = "Objective:\n{$objectiveInfo}\n\n"
                ."Recent History (Last 30 days):\n{$historicalContext}\n\n"
                ."Last 3 Recommendations:\n{$recommendationContext}\n\n"
                ."Today is {$today}.\n\n"
                ."Generate a training plan for the upcoming 7 days, starting from today ({$today}).\n\n"
                .'Think hard about the best training sessions for each day to reach the objective. '
                ."Consider fatigue, recovery, and the target date ({$this->objective->target_date->toDateString()}). "
                .'For each day, provide the type of run, title, description, reasoning, and the date (YYYY-MM-DD).';

            $schema = new ObjectSchema(
                name: 'training_plan_wrapper',
                description: 'A wrapper for the training plan',
                properties: [
                    new ArraySchema(
                        name: 'training_plan',
                        description: 'A list of 7 daily training plans for a runner starting from today',
                        items: new ObjectSchema(
                            name: 'daily_plan',
                            description: 'A structured daily training plan',
                            properties: [
                                new StringSchema('date', 'The date of the workout (YYYY-MM-DD)'),
                                new StringSchema('type', 'The type of run (e.g., Easy Run, Intervals, Long Run, Rest)'),
                                new StringSchema('title', 'A short catchy title for the workout'),
                                new StringSchema('description', 'Detailed instructions for the workout including distance/time and pace if applicable'),
                                new StringSchema('reasoning', 'Explain why this specific workout is recommended for this day based on history and objective'),
                            ],
                            requiredFields: ['date', 'type', 'title', 'description', 'reasoning']
                        ),
                        minItems: 7,
                        maxItems: 7
                    ),
                ],
                requiredFields: ['training_plan']
            );

            $response = Prism::structured()
                ->using(Provider::OpenAI, 'gpt-4o')
                ->withSchema($schema)
                ->withSystemPrompt("You are Lararun's expert running coach. You provide highly personalized and scientifically sound training plans for a full week. You 'think hard' before providing a clear plan for each day.")
                ->withPrompt($prompt)
                ->asStructured();

            $dailyPlans = $response->structured['training_plan'];

            foreach ($dailyPlans as $planData) {
                $recommendation = DailyRecommendation::where('user_id', $this->user->id)
                    ->whereDate('date', $planData['date'])
                    ->first();

                if ($recommendation) {
                    $recommendation->update([
                        'objective_id' => $this->objective->id,
                        'type' => $planData['type'],
                        'title' => $planData['title'],
                        'description' => $planData['description'],
                        'reasoning' => $planData['reasoning'],
                    ]);
                } else {
                    $recommendation = DailyRecommendation::create([
                        'user_id' => $this->user->id,
                        'objective_id' => $this->objective->id,
                        'date' => $planData['date'],
                        'type' => $planData['type'],
                        'title' => $planData['title'],
                        'description' => $planData['description'],
                        'reasoning' => $planData['reasoning'],
                    ]);
                }

                if ($planData['date'] === $startDate) {
                    $this->user->notify(new DailyTrainingPlanNotification($recommendation));
                }
            }

            Log::info("7-day training plan generated and notification sent for user: {$this->user->id}");
        } catch (\Exception $e) {
            Log::error("Failed to generate training plan for user {$this->user->id}: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            throw $e;
        }
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
            ."Target Date: {$this->objective->target_date->toDateString()}\n"
            ."Description: {$this->objective->description}\n"
            ."Preferred Running Days: {$runningDays}";
    }

    /**
     * Get historical context (last 30 days of activities).
     */
    protected function getHistoricalContext(): string
    {
        $historicalActivities = Activity::where('user_id', $this->user->id)
            ->where('start_date', '>=', now()->subDays(30))
            ->orderByDesc('start_date')
            ->limit(10)
            ->get();

        if ($historicalActivities->isEmpty()) {
            return 'No previous activities in the last 30 days.';
        }

        $context = '';
        foreach ($historicalActivities as $activity) {
            $context .= "--- Activity ---\n";
            $context .= "Date: {$activity->start_date->toDateString()}\n";
            $context .= 'Distance: '.round($activity->distance / 1000, 2)." km\n";
            $context .= "Intensity Score: {$activity->intensity_score}\n";
            $context .= "Coach's Evaluation: {$activity->short_evaluation}\n";
        }

        return $context;
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
            $context .= "Date: {$recommendation->date->toDateString()}\n";
            $context .= "Type: {$recommendation->type}\n";
            $context .= "Title: {$recommendation->title}\n";
            $context .= "Description: {$recommendation->description}\n";
        }

        return $context;
    }
}
