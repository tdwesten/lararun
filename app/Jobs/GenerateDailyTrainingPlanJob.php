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
        public Objective $objective
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $todayDate = now()->toDateString();

        Log::info("Generating daily training plan for user: {$this->user->id} on objective: {$this->objective->id}");

        try {
            $historicalContext = $this->getHistoricalContext();
            $recommendationContext = $this->getRecommendationContext();
            $objectiveInfo = $this->getObjectiveInfo();
            $today = now()->format('l, Y-m-d');

            $prompt = "Objective:\n{$objectiveInfo}\n\n"
                ."Recent History (Last 30 days):\n{$historicalContext}\n\n"
                ."Last 3 Recommendations:\n{$recommendationContext}\n\n"
                ."Today is {$today}.\n\n"
                .'Think hard about the best training session for today to reach the objective. '
                ."Consider fatigue, recovery, and the target date ({$this->objective->target_date->toDateString()}). "
                ."If today is NOT one of the scheduled running days, you may still recommend a light recovery run or a full rest day if the history suggests it's needed.";

            $schema = new ObjectSchema(
                name: 'training_plan',
                description: 'A structured daily training plan for a runner',
                properties: [
                    new StringSchema('type', 'The type of run (e.g., Easy Run, Intervals, Long Run, Rest)'),
                    new StringSchema('title', 'A short catchy title for the workout'),
                    new StringSchema('description', 'Detailed instructions for the workout including distance/time and pace if applicable'),
                    new StringSchema('reasoning', 'Explain why this specific workout is recommended today based on history and objective'),
                ],
                requiredFields: ['type', 'title', 'description', 'reasoning']
            );

            $response = Prism::structured()
                ->using(Provider::OpenAI, 'gpt-4o')
                ->withSchema($schema)
                ->withSystemPrompt("You are Lararun's expert running coach. You provide highly personalized and scientifically sound training plans. You 'think hard' before providing a clear plan for the day.")
                ->withPrompt($prompt)
                ->asStructured();

            $data = $response->structured;

            DailyRecommendation::where('user_id', $this->user->id)
                ->whereDate('date', $todayDate)
                ->delete();

            $recommendation = DailyRecommendation::create([
                'user_id' => $this->user->id,
                'objective_id' => $this->objective->id,
                'date' => now()->toDateString(),
                'type' => $data['type'],
                'title' => $data['title'],
                'description' => $data['description'],
                'reasoning' => $data['reasoning'],
            ]);

            $this->user->notify(new DailyTrainingPlanNotification($recommendation));

            Log::info("Daily training plan generated and notification sent for user: {$this->user->id}");
        } catch (\Exception $e) {
            Log::error("Failed to generate daily training plan for user {$this->user->id}: {$e->getMessage()}", [
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
