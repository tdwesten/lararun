<?php

use App\Jobs\EnrichActivityWithAiJob;
use App\Models\Activity;
use App\Models\User;
use App\Notifications\ActivityEvaluatedNotification;
use Illuminate\Support\Facades\Notification;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;

it('enriches an activity with AI evaluations using historical context and sends notification', function () {
    Notification::fake();

    $prismFake = Prism::fake([
        StructuredResponseFake::make()->withStructured([
            'short_evaluation' => 'Short evaluation with context',
            'extended_evaluation' => 'Extended evaluation with context',
        ]),
    ]);

    $user = User::factory()->create();

    // Create a historical activity
    Activity::factory()->create([
        'user_id' => $user->id,
        'start_date' => now()->subDays(5),
        'distance' => 6000,
        'moving_time' => 1800,
    ]);

    $activity = Activity::factory()->create([
        'user_id' => $user->id,
        'start_date' => now(),
        'distance' => 5000,
        'moving_time' => 1500,
        'zone_data_available' => true,
        'z1_time' => 300,
        'z2_time' => 900,
        'z3_time' => 300,
        'z4_time' => 0,
        'z5_time' => 0,
    ]);

    $job = new EnrichActivityWithAiJob($activity);
    $job->handle();

    $activity->refresh();

    expect($activity->short_evaluation)->toBe('Short evaluation with context');
    expect($activity->extended_evaluation)->toBe('Extended evaluation with context');

    // Verify notification was sent
    Notification::assertSentTo(
        $user,
        ActivityEvaluatedNotification::class,
        function ($notification) use ($activity) {
            return $notification->activity->id === $activity->id;
        }
    );

    // Verify that Prism was called with context
    $prismFake->assertRequest(function ($requests) {
        expect($requests)->toHaveCount(1);
        $prompt = $requests[0]->prompt();
        expect($prompt)->toContain('Recent History (Last 30 days):');
        expect($prompt)->toContain('Activity Name:');
        expect($prompt)->toContain('6 km'); // round(6000 / 1000, 2) = 6
    });
});
