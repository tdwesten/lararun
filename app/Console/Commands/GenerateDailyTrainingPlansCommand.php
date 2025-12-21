<?php

namespace App\Console\Commands;

use App\Jobs\GenerateDailyTrainingPlanJob;
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
    protected $description = 'Generate daily training plans for all users with active objectives';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $objectives = Objective::where('status', 'active')->with('user')->get();

        $this->info("Dispatching daily training plan jobs for {$objectives->count()} active objectives...");

        foreach ($objectives as $objective) {
            GenerateDailyTrainingPlanJob::dispatch($objective->user, $objective);
        }

        $this->info('All jobs have been dispatched.');

        return 0;
    }
}
