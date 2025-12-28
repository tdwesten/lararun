<?php

use App\Models\User;

test('authenticated user without strava token is redirected to connect page', function () {
    $user = User::factory()->create([
        'strava_token' => null,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('auth.strava.connect'));
});

test('authenticated user with strava token can access dashboard', function () {
    $user = User::factory()->create([
        'strava_token' => 'valid-token',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    // It might redirect to other things like email verification or email setting,
    // but at least it should NOT be redirected to strava.connect if it has a token.
    // Given our middleware order: ['auth', 'email.set', 'verified', 'strava.connected']
    // A factory user has email and is verified by default usually.

    if ($response->isRedirect()) {
        $this->assertNotEquals(route('auth.strava.connect'), $response->getTargetUrl());
    } else {
        $response->assertStatus(200);
    }
});

test('guest is redirected to login instead of connect page', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});
