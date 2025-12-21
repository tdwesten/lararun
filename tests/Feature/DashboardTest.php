<?php

use App\Models\Activity;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard and see activities', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'strava_id' => '12345',
    ]);

    $activities = Activity::factory()->count(3)->create([
        'user_id' => $user->id,
    ]);

    $latestActivity = $activities->sortByDesc('start_date')->first();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('activities', 3)
            ->where('activities.0.name', $latestActivity->name)
            ->has('activities.0.short_evaluation')
            ->has('currentObjective')
            ->has('todayRecommendation')
        );
});
