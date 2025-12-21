<?php

use App\Models\Activity;
use App\Models\DailyRecommendation;
use App\Models\Objective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    // Mock the email.set middleware if necessary, but here we just ensure email is set (it is by factory)
});

test('guest cannot access activities index', function () {
    $this->get(route('activities.index'))
        ->assertRedirect(route('login'));
});

test('user can access activities index', function () {
    Activity::factory()->count(5)->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('activities.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('activities/index')
            ->has('activities.data', 5)
        );
});

test('user can access activity show page', function () {
    $activity = Activity::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('activities.show', $activity))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('activities/show')
            ->has('activity')
            ->where('activity.id', $activity->id)
        );
});

test('user cannot access another user activity', function () {
    $otherUser = User::factory()->create();
    $activity = Activity::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('activities.show', $activity))
        ->assertForbidden();
});

test('activity show page includes recommendation for the same day', function () {
    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'start_date' => now(),
    ]);

    $objective = Objective::factory()->create(['user_id' => $this->user->id]);
    $recommendation = DailyRecommendation::factory()->create([
        'user_id' => $this->user->id,
        'objective_id' => $objective->id,
        'date' => now()->toDateString(),
    ]);

    $this->actingAs($this->user)
        ->get(route('activities.show', $activity))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('activities/show')
            ->has('recommendation')
            ->where('recommendation.id', $recommendation->id)
        );
});
