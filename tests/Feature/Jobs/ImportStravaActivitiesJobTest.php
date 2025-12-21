<?php

use App\Jobs\EnrichActivityWithAiJob;
use App\Jobs\ImportStravaActivitiesJob;
use App\Models\Activity;
use App\Models\User;
use App\Services\StravaApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('it imports activities and calculates intensity score', function () {
    Queue::fake();
    Log::shouldReceive('info')->atLeast()->once();
    Log::shouldReceive('debug')->atLeast()->once();
    $user = User::factory()->create([
        'strava_token' => 'valid-token',
        'strava_refresh_token' => 'refresh-token',
        'strava_token_expires_at' => now()->addHours(1),
    ]);

    $activitiesData = [
        (object) [
            'id' => '123456',
            'name' => 'Morning Run',
            'type' => 'Run',
            'distance' => 5000,
            'moving_time' => 1500,
            'elapsed_time' => 1600,
            'start_date' => '2023-01-01T08:00:00Z',
        ],
    ];

    $zonesData = [
        (object) [
            'type' => 'heartrate',
            'distribution_buckets' => [
                (object) ['time' => 300], // Z1
                (object) ['time' => 600], // Z2
                (object) ['time' => 400], // Z3
                (object) ['time' => 150], // Z4
                (object) ['time' => 50],  // Z5
            ],
        ],
    ];

    $stravaApiService = Mockery::mock(StravaApiService::class);
    $stravaApiService->shouldReceive('getActivities')
        ->with(Mockery::on(fn ($u) => $u->id === $user->id), 30)
        ->once()
        ->andReturn($activitiesData);

    $stravaApiService->shouldReceive('getActivityWithZones')
        ->with(Mockery::on(fn ($u) => $u->id === $user->id), '123456')
        ->once()
        ->andReturn(['activity' => $activitiesData[0], 'zones' => $zonesData]);

    $job = new ImportStravaActivitiesJob($user);
    $job->handle($stravaApiService);

    $this->assertDatabaseHas('activities', [
        'strava_id' => '123456',
        'name' => 'Morning Run',
        'z1_time' => 300,
        'z2_time' => 600,
        'z3_time' => 400,
        'z4_time' => 150,
        'z5_time' => 50,
    ]);

    $activity = Activity::where('strava_id', '123456')->first();

    // Intensity Score Calculation:
    // (300*1 + 600*2 + 400*3 + 150*4 + 50*5) / 60
    // (300 + 1200 + 1200 + 600 + 250) / 60
    // 3550 / 60 = 59.1666... -> 59.17
    expect((float) $activity->intensity_score)->toBe(59.17);

    Queue::assertPushed(EnrichActivityWithAiJob::class, function ($job) use ($activity) {
        return $job->activity->id === $activity->id;
    });
});
