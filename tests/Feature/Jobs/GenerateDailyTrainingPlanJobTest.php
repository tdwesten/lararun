<?php

use App\Jobs\GenerateWeeklyTrainingPlanJob;
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
            'training_plan' => [
                [
                    'date' => now()->toDateString(),
                    'type' => 'Easy Run',
                    'title' => 'Recovery Jog',
                    'description' => '30 minutes at easy pace',
                    'reasoning' => 'Based on your recent heavy sessions.',
                ],
                [
                    'date' => now()->addDay()->toDateString(),
                    'type' => 'Rest',
                    'title' => 'Rest Day',
                    'description' => 'No running today',
                    'reasoning' => 'Recovery is key.',
                ],
                [
                    'date' => now()->addDays(2)->toDateString(),
                    'type' => 'Easy Run',
                    'title' => 'Easy 5k',
                    'description' => '5km at comfortable pace',
                    'reasoning' => 'Building base.',
                ],
                [
                    'date' => now()->addDays(3)->toDateString(),
                    'type' => 'Intervals',
                    'title' => 'Speed Work',
                    'description' => '8x400m',
                    'reasoning' => 'Improving pace.',
                ],
                [
                    'date' => now()->addDays(4)->toDateString(),
                    'type' => 'Easy Run',
                    'title' => 'Short Easy Run',
                    'description' => '20 mins easy',
                    'reasoning' => 'Active recovery.',
                ],
                [
                    'date' => now()->addDays(5)->toDateString(),
                    'type' => 'Long Run',
                    'title' => 'Weekend Long Run',
                    'description' => '10km long run',
                    'reasoning' => 'Endurance building.',
                ],
                [
                    'date' => now()->addDays(6)->toDateString(),
                    'type' => 'Rest',
                    'title' => 'Rest Day',
                    'description' => 'Full recovery',
                    'reasoning' => 'Prepare for next week.',
                ],
            ],
        ]),
    ]);

    $job = new GenerateWeeklyTrainingPlanJob($user, $objective);
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
            'training_plan' => [
                [
                    'date' => $todayDate,
                    'type' => 'Intervals',
                    'title' => 'New Title',
                    'description' => 'Run fast then slow',
                    'reasoning' => 'Because I said so.',
                ],
                [
                    'date' => now()->addDay()->toDateString(),
                    'type' => 'Rest',
                    'title' => 'Rest Day',
                    'description' => 'No running today',
                    'reasoning' => 'Recovery is key.',
                ],
                [
                    'date' => now()->addDays(2)->toDateString(),
                    'type' => 'Easy Run',
                    'title' => 'Easy 5k',
                    'description' => '5km at comfortable pace',
                    'reasoning' => 'Building base.',
                ],
                [
                    'date' => now()->addDays(3)->toDateString(),
                    'type' => 'Intervals',
                    'title' => 'Speed Work',
                    'description' => '8x400m',
                    'reasoning' => 'Improving pace.',
                ],
                [
                    'date' => now()->addDays(4)->toDateString(),
                    'type' => 'Easy Run',
                    'title' => 'Short Easy Run',
                    'description' => '20 mins easy',
                    'reasoning' => 'Active recovery.',
                ],
                [
                    'date' => now()->addDays(5)->toDateString(),
                    'type' => 'Long Run',
                    'title' => 'Weekend Long Run',
                    'description' => '10km long run',
                    'reasoning' => 'Endurance building.',
                ],
                [
                    'date' => now()->addDays(6)->toDateString(),
                    'type' => 'Rest',
                    'title' => 'Rest Day',
                    'description' => 'Full recovery',
                    'reasoning' => 'Prepare for next week.',
                ],
            ],
        ]),
    ]);

    $job = new GenerateWeeklyTrainingPlanJob($user, $objective, force: true, sendNotification: true);
    $job->handle();

    // Verify the old one was updated
    expect(DailyRecommendation::where('user_id', $user->id)->whereDate('date', $todayDate)->count())->toBe(1);

    $newRecommendation = DailyRecommendation::where('user_id', $user->id)
        ->whereDate('date', $todayDate)
        ->first();

    expect($newRecommendation->title)->toBe('New Title');
    expect($newRecommendation->id)->toBe($existing->id);

    Notification::assertSentTo($user, DailyTrainingPlanNotification::class);
});
