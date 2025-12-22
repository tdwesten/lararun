<?php

use App\Jobs\EnrichActivityWithAiJob;
use App\Jobs\GenerateDailyTrainingPlanJob;
use App\Models\Activity;
use App\Models\Objective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake();
});

test('creating an activity dispatches enrich and training plan jobs', function () {
    Queue::fake();

    $user = User::factory()->create();
    $objective = Objective::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
    ]);

    $activity = Activity::factory()->create([
        'user_id' => $user->id,
    ]);

    Queue::assertPushed(EnrichActivityWithAiJob::class, function ($job) use ($activity) {
        return $job->activity->id === $activity->id;
    });

    Queue::assertPushed(GenerateDailyTrainingPlanJob::class, function ($job) use ($user, $objective) {
        return $job->user->id === $user->id && $job->objective->id === $objective->id;
    });
});

test('updating an activity performance data dispatches jobs', function () {
    $user = User::factory()->create();
    $objective = Objective::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
    ]);

    $activity = Activity::withoutEvents(function () use ($user) {
        return Activity::factory()->create([
            'user_id' => $user->id,
            'distance' => 5000,
        ]);
    });

    Queue::fake();

    $activity->update(['distance' => 6000]);

    Queue::assertPushed(EnrichActivityWithAiJob::class);
    Queue::assertPushed(GenerateDailyTrainingPlanJob::class);
});

test('updating non-performance data does not dispatch jobs', function () {
    $user = User::factory()->create();
    $activity = Activity::withoutEvents(function () use ($user) {
        return Activity::factory()->create([
            'user_id' => $user->id,
            'short_evaluation' => null,
        ]);
    });

    Queue::fake();

    $activity->update(['short_evaluation' => 'Updated evaluation']);

    Queue::assertNotPushed(EnrichActivityWithAiJob::class);
    Queue::assertNotPushed(GenerateDailyTrainingPlanJob::class);
});

test('creating an activity without active objective only dispatches enrich job', function () {
    Queue::fake();

    $user = User::factory()->create();
    // No active objective

    $activity = Activity::factory()->create([
        'user_id' => $user->id,
    ]);

    Queue::assertPushed(EnrichActivityWithAiJob::class);
    Queue::assertNotPushed(GenerateDailyTrainingPlanJob::class);
});
