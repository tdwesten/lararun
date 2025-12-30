<?php

namespace App\Observers;

use App\Jobs\EnrichActivityWithAiJob;
use App\Jobs\GenerateWeeklyTrainingPlanJob;
use App\Models\Activity;
use App\Services\PersonalRecordService;

class ActivityObserver
{
    /**
     * Handle the Activity "created" event.
     */
    public function created(Activity $activity): void
    {
        $this->dispatchJobs($activity);
        $this->checkPersonalRecords($activity);
        $this->calculateRecovery($activity);
    }

    /**
     * Handle the Activity "updated" event.
     */
    public function updated(Activity $activity): void
    {
        $relevantFields = ['distance', 'moving_time', 'elapsed_time', 'z1_time', 'z2_time', 'z3_time', 'z4_time', 'z5_time'];
        $changes = array_keys($activity->getChanges());

        // Only dispatch if performance data changed
        if (! empty(array_intersect($changes, $relevantFields))) {
            $this->dispatchJobs($activity);
            $this->checkPersonalRecords($activity);
            $this->calculateRecovery($activity);
        }
    }

    /**
     * Dispatch the enrichment and training plan jobs.
     */
    protected function dispatchJobs(Activity $activity): void
    {
        // Dispatch individual activity evaluation
        EnrichActivityWithAiJob::dispatch($activity);

        // Dispatch overall training plan update if user has an active objective
        $objective = $activity->user->currentObjective;

        if ($objective) {
            GenerateWeeklyTrainingPlanJob::dispatch($activity->user, $objective, force: false, sendNotification: false);
        }
    }

    /**
     * Check and update personal records for this activity.
     */
    protected function checkPersonalRecords(Activity $activity): void
    {
        $service = app(PersonalRecordService::class);
        $service->checkAndUpdateRecords($activity);
    }

    /**
     * Calculate recovery score and estimated recovery time.
     */
    protected function calculateRecovery(Activity $activity): void
    {
        if ($activity->type !== 'Run' || ! $activity->intensity_score) {
            return;
        }

        // Calculate recovery based on intensity score
        // Higher intensity = lower immediate recovery score
        $recoveryScore = max(0, 10 - $activity->intensity_score);

        // Estimate recovery hours based on intensity and distance
        $distanceKm = $activity->distance / 1000;
        $baseRecoveryHours = $distanceKm * 2; // 2 hours per km as baseline
        $intensityMultiplier = $activity->intensity_score / 5; // 1-2x based on intensity
        $estimatedRecoveryHours = (int) round($baseRecoveryHours * $intensityMultiplier);

        $activity->updateQuietly([
            'recovery_score' => $recoveryScore,
            'estimated_recovery_hours' => $estimatedRecoveryHours,
        ]);
    }
}
