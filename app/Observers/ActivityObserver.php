<?php

namespace App\Observers;

use App\Jobs\EnrichActivityWithAiJob;
use App\Jobs\GenerateWeeklyTrainingPlanJob;
use App\Models\Activity;

class ActivityObserver
{
    /**
     * Handle the Activity "created" event.
     */
    public function created(Activity $activity): void
    {
        $this->dispatchJobs($activity);
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
}
