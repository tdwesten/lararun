<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('it redirects to strava', function () {
    $response = $this->get(route('auth.strava.redirect'));

    $response->assertRedirect();
    $this->assertStringContainsString('strava.com', $response->getTargetUrl());
});

test('it handles strava callback and creates a new user', function () {
    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->shouldReceive('getId')->andReturn('12345');
    $abstractUser->shouldReceive('getName')->andReturn('John Doe');
    $abstractUser->shouldReceive('getEmail')->andReturn('john@example.com');
    $abstractUser->token = 'fake-token';
    $abstractUser->refreshToken = 'fake-refresh-token';

    Socialite::shouldReceive('driver')->with('strava')->andReturn(Mockery::mock('Laravel\Socialite\Two\AbstractProvider')->shouldReceive('user')->andReturn($abstractUser)->getMock());

    $response = $this->get(route('auth.strava.callback'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    $user = User::where('strava_id', '12345')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john@example.com')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->strava_token)->toBe('fake-token')
        ->and($user->strava_refresh_token)->toBe('fake-refresh-token');
});

test('it handles strava callback and logs in existing user', function () {
    $user = User::factory()->create([
        'strava_id' => '12345',
        'email' => 'john@example.com',
    ]);

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->shouldReceive('getId')->andReturn('12345');
    $abstractUser->shouldReceive('getName')->andReturn('John Doe Updated');
    $abstractUser->shouldReceive('getEmail')->andReturn('john@example.com');
    $abstractUser->token = 'new-token';
    $abstractUser->refreshToken = 'new-refresh-token';

    Socialite::shouldReceive('driver')->with('strava')->andReturn(Mockery::mock('Laravel\Socialite\Two\AbstractProvider')->shouldReceive('user')->andReturn($abstractUser)->getMock());

    $response = $this->get(route('auth.strava.callback'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->name)->toBe('John Doe Updated')
        ->and($user->strava_token)->toBe('new-token');
});

test('it handles strava callback and creates a new user without email', function () {
    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->shouldReceive('getId')->andReturn('67890');
    $abstractUser->shouldReceive('getName')->andReturn('Jane Doe');
    $abstractUser->shouldReceive('getEmail')->andReturn(null);
    $abstractUser->token = 'fake-token-2';
    $abstractUser->refreshToken = 'fake-refresh-token-2';

    Socialite::shouldReceive('driver')->with('strava')->andReturn(Mockery::mock('Laravel\Socialite\Two\AbstractProvider')->shouldReceive('user')->andReturn($abstractUser)->getMock());

    $response = $this->get(route('auth.strava.callback'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    $user = User::where('strava_id', '67890')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Jane Doe')
        ->and($user->email)->toBeNull();
});

test('it does not overwrite existing email if strava returns null email', function () {
    $user = User::factory()->create([
        'strava_id' => '12345',
        'email' => 'existing@example.com',
    ]);

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->shouldReceive('getId')->andReturn('12345');
    $abstractUser->shouldReceive('getName')->andReturn('John Doe');
    $abstractUser->shouldReceive('getEmail')->andReturn(null);
    $abstractUser->token = 'fake-token';
    $abstractUser->refreshToken = 'fake-refresh-token';

    Socialite::shouldReceive('driver')->with('strava')->andReturn(Mockery::mock('Laravel\Socialite\Two\AbstractProvider')->shouldReceive('user')->andReturn($abstractUser)->getMock());

    $response = $this->get(route('auth.strava.callback'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->email)->toBe('existing@example.com');
});

test('authenticated user can connect strava account', function () {
    $user = User::factory()->create([
        'strava_id' => null,
        'strava_token' => null,
        'email' => 'original@example.com',
    ]);

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->shouldReceive('getId')->andReturn('55555');
    $abstractUser->shouldReceive('getName')->andReturn('Strava User');
    $abstractUser->shouldReceive('getEmail')->andReturn('strava@example.com');
    $abstractUser->token = 'connect-token';
    $abstractUser->refreshToken = 'connect-refresh-token';
    $abstractUser->expiresIn = 3600;

    Socialite::shouldReceive('driver')->with('strava')->andReturn(Mockery::mock('Laravel\Socialite\Two\AbstractProvider')->shouldReceive('user')->andReturn($abstractUser)->getMock());

    $response = $this->actingAs($user)->get(route('auth.strava.callback'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->strava_id)->toBe('55555')
        ->and($user->strava_token)->toBe('connect-token')
        ->and($user->email)->toBe('original@example.com');
});

test('cannot connect strava account if already taken by another user', function () {
    User::factory()->create([
        'strava_id' => '12345',
    ]);

    $user = User::factory()->create([
        'strava_id' => null,
    ]);

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->shouldReceive('getId')->andReturn('12345');
    $abstractUser->shouldReceive('getName')->andReturn('Strava User');
    $abstractUser->shouldReceive('getEmail')->andReturn('strava@example.com');
    $abstractUser->token = 'connect-token';
    $abstractUser->refreshToken = 'connect-refresh-token';
    $abstractUser->expiresIn = 3600;

    Socialite::shouldReceive('driver')->with('strava')->andReturn(Mockery::mock('Laravel\Socialite\Two\AbstractProvider')->shouldReceive('user')->andReturn($abstractUser)->getMock());

    $response = $this->actingAs($user)->get(route('auth.strava.callback'));

    $response->assertRedirect(route('auth.strava.connect'));
    $response->assertSessionHas('error', 'This Strava account is already connected to another user.');

    $user->refresh();
    expect($user->strava_id)->toBeNull();
});

test('strava connect screen can be rendered', function () {
    $user = User::factory()->create([
        'strava_token' => null,
    ]);

    $response = $this->actingAs($user)->get(route('auth.strava.connect'));

    $response->assertStatus(200);
});

test('strava connect screen redirects if already connected', function () {
    $user = User::factory()->create([
        'strava_token' => 'some-token',
    ]);

    $response = $this->actingAs($user)->get(route('auth.strava.connect'));

    $response->assertRedirect(route('dashboard'));
});
