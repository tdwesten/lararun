<?php

namespace App\Console\Commands;

use App\Jobs\GenerateWeeklyTrainingPlanJob;
use App\Models\Objective;
use Illuminate\Console\Command;

class GenerateDailyTrainingPlansCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-daily-training-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate training plans for the upcoming 7 days for all users with active objectives';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // First, mark objectives past their target date as completed
        $expiredObjectives = Objective::where('status', 'active')
            ->whereNotNull('target_date')
            ->where('target_date', '<', now())
            ->update(['status' => 'completed']);

        if ($expiredObjectives > 0) {
            $this->info("Marked {$expiredObjectives} expired objectives as completed.");
        }

        // Get only active objectives with future target dates (or no target date)
        $objectives = Objective::where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('target_date')
                    ->orWhere('target_date', '>=', now());
            })
            ->with('user')
            ->get();

        if ($objectives->isEmpty()) {
            $this->info('No active objectives found. Skipping training plan generation.');

            return 0;
        }

        $this->info("Dispatching daily training plan jobs for {$objectives->count()} active objectives...");

        foreach ($objectives as $objective) {
            GenerateWeeklyTrainingPlanJob::dispatch($objective->user, $objective, force: false, sendNotification: true);
        }

        $this->info('All jobs have been dispatched.');

        return 0;
    }
}
