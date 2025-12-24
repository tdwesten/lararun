<?php

use App\Jobs\GenerateWeeklyTrainingPlanJob;
use App\Models\Objective;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('dispatches jobs for all users with active objectives', function () {
    Queue::fake();

    $user1 = User::factory()->create();
    $objective1 = Objective::factory()->create([
        'user_id' => $user1->id,
        'status' => 'active',
    ]);

    $user2 = User::factory()->create();
    $objective2 = Objective::factory()->create([
        'user_id' => $user2->id,
        'status' => 'active',
    ]);

    $user3 = User::factory()->create();
    $objective3 = Objective::factory()->create([
        'user_id' => $user3->id,
        'status' => 'completed', // Should be ignored
    ]);

    $this->artisan('app:generate-daily-training-plans')
        ->assertExitCode(0);

    Queue::assertPushed(GenerateWeeklyTrainingPlanJob::class, 2);
    Queue::assertPushed(GenerateWeeklyTrainingPlanJob::class, function ($job) use ($user1, $objective1) {
        return $job->user->id === $user1->id && $job->objective->id === $objective1->id;
    });
    Queue::assertPushed(GenerateWeeklyTrainingPlanJob::class, function ($job) use ($user2, $objective2) {
        return $job->user->id === $user2->id && $job->objective->id === $objective2->id;
    });
});

it('dispatches jobs with sendNotification set to true', function () {
    Queue::fake();

    $user = User::factory()->create();
    $objective = Objective::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
    ]);

    $this->artisan('app:generate-daily-training-plans')
        ->assertExitCode(0);

    Queue::assertPushed(GenerateWeeklyTrainingPlanJob::class, function ($job) use ($user, $objective) {
        return $job->user->id === $user->id
            && $job->objective->id === $objective->id
            && $job->sendNotification === true;
    });
});
