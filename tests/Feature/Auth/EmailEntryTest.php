<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('authenticated user without email is redirected to email entry page', function () {
    $user = User::factory()->create(['email' => null]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('auth.email.show'));
});

test('authenticated user without email is NOT redirected to email entry page from home page', function () {
    $user = User::factory()->create(['email' => null]);

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk();
});

test('authenticated user with email is not redirected to email entry page', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

test('email entry page can be rendered', function () {
    $user = User::factory()->create(['email' => null]);

    $response = $this->actingAs($user)->get(route('auth.email.show'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('auth/email-entry'));
});

test('user can submit email and receive verification notification', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => null]);

    $response = $this->actingAs($user)->post(route('auth.email.store'), [
        'email' => 'new-email@example.com',
    ]);

    $user->refresh();
    expect($user->email)->toBe('new-email@example.com');
    $response->assertRedirect(route('verification.notice'));

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('user cannot submit an already taken email', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create(['email' => null]);

    $response = $this->actingAs($user)->post(route('auth.email.store'), [
        'email' => 'taken@example.com',
    ]);

    $response->assertSessionHasErrors('email');
    expect($user->refresh()->email)->toBeNull();
});
