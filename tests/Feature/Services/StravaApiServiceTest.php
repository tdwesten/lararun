<?php

use App\Models\User;
use App\Services\StravaApiService;
use CodeToad\Strava\Strava;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it refreshes token if expired', function () {
    $user = User::factory()->create([
        'strava_token' => 'old-token',
        'strava_refresh_token' => 'refresh-token',
        'strava_token_expires_at' => now()->subMinutes(10),
    ]);

    $stravaMock = Mockery::mock(Strava::class);
    $stravaMock->shouldReceive('refreshToken')
        ->with('refresh-token')
        ->once()
        ->andReturn((object) [
            'access_token' => 'new-token',
            'refresh_token' => 'new-refresh-token',
            'expires_in' => 3600,
        ]);

    $service = new StravaApiService($stravaMock);

    // We call a protected method via a public one or just test the public one that uses it
    $stravaMock->shouldReceive('activities')
        ->with('new-token', 1, 30)
        ->andReturn([]);

    $service->getActivities($user);

    $user->refresh();
    expect($user->strava_token)->toBe('new-token')
        ->and($user->strava_refresh_token)->toBe('new-refresh-token')
        ->and($user->strava_token_expires_at)->toBeGreaterThan(now());
});

test('it does not refresh token if still valid', function () {
    $user = User::factory()->create([
        'strava_token' => 'valid-token',
        'strava_refresh_token' => 'refresh-token',
        'strava_token_expires_at' => now()->addHours(1),
    ]);

    $stravaMock = Mockery::mock(Strava::class);
    $stravaMock->shouldNotReceive('refreshToken');
    $stravaMock->shouldReceive('activities')
        ->with('valid-token', 1, 30)
        ->andReturn([]);

    $service = new StravaApiService($stravaMock);
    $service->getActivities($user);

    $user->refresh();
    expect($user->strava_token)->toBe('valid-token');
});
