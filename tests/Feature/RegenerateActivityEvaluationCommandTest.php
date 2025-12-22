<?php

use App\Jobs\EnrichActivityWithAiJob;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('it can regenerate evaluation for a specific activity', function () {
    $activity = Activity::withoutEvents(fn () => Activity::factory()->create());
    Queue::fake();

    $this->artisan("app:regenerate-activity-evaluation {$activity->id}")
        ->expectsOutput('Dispatching enrichment jobs for 1 activities...')
        ->assertExitCode(0);

    Queue::assertPushed(EnrichActivityWithAiJob::class, function ($job) use ($activity) {
        return $job->activity->id === $activity->id;
    });
});

test('it can regenerate evaluations for a specific user', function () {
    $user = User::factory()->create();
    $activities = Activity::withoutEvents(fn () => Activity::factory()->count(3)->create(['user_id' => $user->id]));
    Activity::withoutEvents(fn () => Activity::factory()->create()); // Another user's activity
    Queue::fake();

    $this->artisan("app:regenerate-activity-evaluation --user={$user->id}")
        ->expectsConfirmation('Are you sure you want to regenerate evaluations for 3 activities?', 'yes')
        ->expectsOutput('Dispatching enrichment jobs for 3 activities...')
        ->assertExitCode(0);

    Queue::assertPushed(EnrichActivityWithAiJob::class, 3);
});

test('it can regenerate all evaluations', function () {
    Activity::withoutEvents(fn () => Activity::factory()->count(5)->create());
    Queue::fake();

    $this->artisan('app:regenerate-activity-evaluation --all')
        ->expectsConfirmation('Are you sure you want to regenerate evaluations for 5 activities?', 'yes')
        ->expectsOutput('Dispatching enrichment jobs for 5 activities...')
        ->assertExitCode(0);

    Queue::assertPushed(EnrichActivityWithAiJob::class, 5);
});

test('it warns when no activities found', function () {
    $this->artisan('app:regenerate-activity-evaluation 999')
        ->expectsOutput('No activities found matching your criteria.')
        ->assertExitCode(0);
});

test('it fails when no parameters provided', function () {
    $this->artisan('app:regenerate-activity-evaluation')
        ->expectsOutput('Please provide an activity_id, use --user={id}, or use the --all flag.')
        ->assertExitCode(1);
});
