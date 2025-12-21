<?php

use App\Jobs\GenerateDailyTrainingPlanJob;
use App\Models\Activity;
use App\Models\DailyRecommendation;
use App\Models\Objective;
use App\Models\User;
use App\Notifications\DailyTrainingPlanNotification;
use Illuminate\Support\Facades\Notification;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;

it('generates a daily training plan using AI and sends a notification', function () {
    Notification::fake();

    $jsonResponse = [
        'type' => 'Intervals',
        'title' => 'Speed Demon Intervals',
        'description' => '10 min warm up, then 8x400m at goal pace, 5 min cool down.',
        'reasoning' => 'Based on your recent easy runs, you are ready for some speed work to reach your 5K goal.',
    ];

    $prismFake = Prism::fake([
        StructuredResponseFake::make()->withStructured($jsonResponse),
    ]);

    $user = User::factory()->create(['name' => 'John Doe']);
    $objective = Objective::factory()->create([
        'user_id' => $user->id,
        'type' => '5 km',
        'target_date' => now()->addMonth(),
        'status' => 'active',
        'running_days' => ['Tuesday', 'Thursday', 'Saturday'],
    ]);

    // Create some historical activities
    Activity::factory()->count(3)->create([
        'user_id' => $user->id,
        'start_date' => now()->subDays(2),
        'distance' => 5000,
        'short_evaluation' => 'Good steady run.',
    ]);

    $job = new GenerateDailyTrainingPlanJob($user, $objective);
    $job->handle();

    // Assert recommendation was created
    $recommendation = DailyRecommendation::where('user_id', $user->id)->first();
    expect($recommendation)->not->toBeNull();
    expect($recommendation->type)->toBe('Intervals');
    expect($recommendation->title)->toBe('Speed Demon Intervals');

    // Assert notification was sent
    Notification::assertSentTo(
        $user,
        DailyTrainingPlanNotification::class,
        function ($notification) use ($recommendation) {
            return $notification->recommendation->id === $recommendation->id;
        }
    );

    // Verify Prism request
    $prismFake->assertRequest(function ($requests) {
        expect($requests)->toHaveCount(1);
        $request = $requests[0];
        expect($request->prompt())->toContain('Objective:');
        expect($request->prompt())->toContain('5 km');
        expect($request->prompt())->toContain('Tuesday, Thursday, Saturday');

        return true;
    });
});

it('does not generate a daily training plan if one already exists for today', function () {
    Notification::fake();
    $prismFake = Prism::fake([]);

    $user = User::factory()->create();
    $objective = Objective::factory()->create(['user_id' => $user->id]);

    // Create an existing recommendation for today
    DailyRecommendation::factory()->create([
        'user_id' => $user->id,
        'objective_id' => $objective->id,
        'date' => now()->toDateString(),
    ]);

    $job = new GenerateDailyTrainingPlanJob($user, $objective);
    $job->handle();

    // Verify no new recommendation was created
    expect(DailyRecommendation::where('user_id', $user->id)->count())->toBe(1);

    // Verify no notification was sent (for the second attempt)
    Notification::assertNothingSent();

    // Verify Prism was not called
    $prismFake->assertCallCount(0);
});
