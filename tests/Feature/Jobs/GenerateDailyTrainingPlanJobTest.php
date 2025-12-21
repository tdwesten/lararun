<?php

use App\Jobs\GenerateDailyTrainingPlanJob;
use App\Models\DailyRecommendation;
use App\Models\Objective;
use App\Models\User;
use App\Notifications\DailyTrainingPlanNotification;
use Illuminate\Support\Facades\Notification;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;

it('includes the last 3 daily recommendations in the prompt', function () {
    Notification::fake();

    $user = User::factory()->create();
    $objective = Objective::factory()->create(['user_id' => $user->id]);

    // Create 4 recommendations on different dates
    for ($i = 4; $i >= 1; $i--) {
        DailyRecommendation::factory()->create([
            'user_id' => $user->id,
            'objective_id' => $objective->id,
            'date' => now()->subDays($i)->toDateString(),
        ]);
    }

    $allRecommendations = DailyRecommendation::where('user_id', $user->id)
        ->orderByDesc('date')
        ->orderByDesc('id')
        ->get();

    $lastThree = $allRecommendations->take(3);
    $oldest = $allRecommendations->last();

    $fake = Prism::fake([
        StructuredResponseFake::make()->withStructured([
            'type' => 'Easy Run',
            'title' => 'Recovery Jog',
            'description' => '30 minutes at easy pace',
            'reasoning' => 'Based on your recent heavy sessions.',
        ]),
    ]);

    $job = new GenerateDailyTrainingPlanJob($user, $objective);
    $job->handle();

    $fake->assertRequest(function ($recorded) use ($lastThree, $oldest) {
        $request = $recorded[0];
        $prompt = $request->prompt();

        foreach ($lastThree as $recommendation) {
            expect($prompt)->toContain($recommendation->title);
        }

        expect($prompt)->not->toContain($oldest->title);
        expect($prompt)->toContain('Last 3 Recommendations:');
    });
});

it('replaces an existing daily recommendation for today', function () {
    Notification::fake();

    $user = User::factory()->create();
    $objective = Objective::factory()->create(['user_id' => $user->id]);

    $todayDate = now()->toDateString();

    // Create an existing recommendation for today
    $existing = DailyRecommendation::factory()->create([
        'user_id' => $user->id,
        'objective_id' => $objective->id,
        'date' => $todayDate,
        'title' => 'Old Title',
    ]);

    Prism::fake([
        StructuredResponseFake::make()->withStructured([
            'type' => 'Intervals',
            'title' => 'New Title',
            'description' => 'Run fast then slow',
            'reasoning' => 'Because I said so.',
        ]),
    ]);

    $job = new GenerateDailyTrainingPlanJob($user, $objective, force: true);
    $job->handle();

    // Verify the old one is gone and new one exists
    expect(DailyRecommendation::where('user_id', $user->id)->whereDate('date', $todayDate)->count())->toBe(1);

    $newRecommendation = DailyRecommendation::where('user_id', $user->id)
        ->whereDate('date', $todayDate)
        ->first();

    expect($newRecommendation->title)->toBe('New Title');
    expect($newRecommendation->id)->not->toBe($existing->id);

    Notification::assertSentTo($user, DailyTrainingPlanNotification::class);
});
