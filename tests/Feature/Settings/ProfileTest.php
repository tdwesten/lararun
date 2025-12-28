<?php

use App\Models\User;
use Illuminate\Support\Facades\App;

test('profile information can be updated including locale', function () {
    $user = User::factory()->create([
        'locale' => 'en',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch('/settings/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'locale' => 'nl',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/profile');

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->locale)->toBe('nl');
});

test('application locale is set based on user preference', function () {
    $user = User::factory()->create([
        'locale' => 'nl',
    ]);

    $this->actingAs($user)
        ->get('/settings/profile');

    expect(App::getLocale())->toBe('nl');
});

use Inertia\Testing\AssertableInertia as Assert;

test('translations are shared with inertia', function () {
    $user = User::factory()->create([
        'locale' => 'nl',
    ]);

    $this->actingAs($user)
        ->get('/settings/profile')
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/profile')
            ->has('translations')
            ->where('locale', 'nl')
            ->where('translations.Save', 'Opslaan')
        );
});
