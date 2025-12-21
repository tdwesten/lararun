<?php

namespace App\Console\Commands;

use App\Jobs\EnrichActivityWithAiJob;
use App\Models\Activity;
use Illuminate\Console\Command;

class RegenerateActivityEvaluationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:regenerate-activity-evaluation {activity_id? : The ID of the activity} {--user= : Regenerate all activities for a specific user ID} {--all : Regenerate all activities}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate the AI evaluation for one or more activities';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $activityId = $this->argument('activity_id');
        $userId = $this->option('user');
        $all = $this->option('all');

        if (! $activityId && ! $userId && ! $all) {
            $this->error('Please provide an activity_id, use --user={id}, or use the --all flag.');

            return 1;
        }

        $query = Activity::query();

        if ($activityId) {
            $query->where('id', $activityId);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->warn('No activities found matching your criteria.');

            return 0;
        }

        if ($count > 1 && ! $this->confirm("Are you sure you want to regenerate evaluations for {$count} activities?")) {
            return 0;
        }

        $this->info("Dispatching enrichment jobs for {$count} activities...");

        $query->each(function (Activity $activity) {
            EnrichActivityWithAiJob::dispatch($activity);
        });

        $this->info('All jobs have been dispatched to the queue.');

        return 0;
    }
}
