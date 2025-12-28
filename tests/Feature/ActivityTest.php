<?php

use App\Jobs\EnrichActivityWithAiJob;
use App\Models\Activity;
use App\Models\DailyRecommendation;
use App\Models\Objective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake();
    $this->user = User::factory()->create([
        'email_verified_at' => now(),
        'strava_token' => 'fake-token',
    ]);
});

test('guest cannot access activities index', function () {
    $this->get(route('activities.index'))
        ->assertRedirect(route('login'));
});

test('user can access activities index', function () {
    $user = $this->user;
    Activity::withoutEvents(function () use ($user) {
        Activity::factory()->count(5)->create(['user_id' => $user->id]);
    });

    $this->actingAs($this->user)
        ->get(route('activities.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('activities/index')
            ->has('activities.data', 5)
        );
});

test('user can access activity show page', function () {
    $user = $this->user;
    $activity = Activity::withoutEvents(function () use ($user) {
        return Activity::factory()->create(['user_id' => $user->id]);
    });

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
    $activity = Activity::withoutEvents(function () use ($otherUser) {
        return Activity::factory()->create(['user_id' => $otherUser->id]);
    });

    $this->actingAs($this->user)
        ->get(route('activities.show', $activity))
        ->assertForbidden();
});

test('activity show page includes recommendation for the same day', function () {
    $user = $this->user;
    $activity = Activity::withoutEvents(function () use ($user) {
        return Activity::factory()->create([
            'user_id' => $user->id,
            'start_date' => now(),
        ]);
    });

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

test('activity show page dispatches enrich job if evaluation is missing', function () {
    $activity = Activity::withoutEvents(function () {
        return Activity::factory()->create([
            'user_id' => $this->user->id,
            'short_evaluation' => null,
            'extended_evaluation' => null,
        ]);
    });

    Queue::fake();

    $this->actingAs($this->user)
        ->get(route('activities.show', $activity))
        ->assertOk();

    Queue::assertPushed(EnrichActivityWithAiJob::class, function ($job) use ($activity) {
        return $job->activity->id === $activity->id;
    });
});

test('activity show page does not dispatch enrich job if evaluation exists', function () {
    $activity = Activity::withoutEvents(function () {
        return Activity::factory()->create([
            'user_id' => $this->user->id,
            'short_evaluation' => 'Great run!',
            'extended_evaluation' => 'You did well today.',
        ]);
    });

    Queue::fake();

    $this->actingAs($this->user)
        ->get(route('activities.show', $activity))
        ->assertOk();

    Queue::assertNotPushed(EnrichActivityWithAiJob::class);
});

test('activity show page does not dispatch multiple enrich jobs if one is already in progress', function () {
    $activity = Activity::withoutEvents(function () {
        return Activity::factory()->create([
            'user_id' => $this->user->id,
            'short_evaluation' => null,
            'extended_evaluation' => null,
        ]);
    });

    Queue::fake();

    $this->actingAs($this->user)->get(route('activities.show', $activity));
    $this->actingAs($this->user)->get(route('activities.show', $activity));

    Queue::assertPushed(EnrichActivityWithAiJob::class, 1);
});
