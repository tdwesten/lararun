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
        'training_plan' => [
            [
                'date' => now()->toDateString(),
                'type' => 'Intervals',
                'title' => 'Speed Demon Intervals',
                'description' => '10 min warm up, then 8x400m at goal pace, 5 min cool down.',
                'reasoning' => 'Based on your recent easy runs, you are ready for some speed work to reach your 5K goal.',
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

    // Assert recommendations were created for 7 days
    expect(DailyRecommendation::where('user_id', $user->id)->count())->toBe(7);
    $recommendation = DailyRecommendation::where('user_id', $user->id)
        ->whereDate('date', now()->toDateString())
        ->first();
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

it('does not generate a daily training plan if all 7 days already exist', function () {
    Notification::fake();
    $prismFake = Prism::fake([]);

    $user = User::factory()->create();
    $objective = Objective::factory()->create(['user_id' => $user->id]);

    // Create 7 existing recommendations for the next 7 days
    for ($i = 0; $i < 7; $i++) {
        DailyRecommendation::factory()->create([
            'user_id' => $user->id,
            'objective_id' => $objective->id,
            'date' => now()->addDays($i)->toDateString(),
        ]);
    }

    $job = new GenerateDailyTrainingPlanJob($user, $objective);
    $job->handle();

    // Verify no new recommendation was created (still 7)
    expect(DailyRecommendation::where('user_id', $user->id)->count())->toBe(7);

    // Verify no notification was sent
    Notification::assertNothingSent();

    // Verify Prism was not called
    $prismFake->assertCallCount(0);
});
