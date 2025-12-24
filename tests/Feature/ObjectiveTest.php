<?php

use App\Models\Objective;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake();
    $this->user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
});

test('user can see objectives index', function () {
    $response = $this->actingAs($this->user)->get(route('objectives.index'));

    $response->assertStatus(200);
});

test('user can see objective detail', function () {
    $objective = Objective::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('objectives.show', $objective));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('objectives/show')
        ->has('objective')
    );
});

test('user can see objective detail with 7 upcoming recommendations sorted', function () {
    $objective = Objective::factory()->create([
        'user_id' => $this->user->id,
    ]);

    // Past recommendation (should be excluded)
    \App\Models\DailyRecommendation::factory()->create([
        'user_id' => $this->user->id,
        'objective_id' => $objective->id,
        'date' => now()->subDay()->toDateString(),
    ]);

    // Today's recommendation
    $todayRec = \App\Models\DailyRecommendation::factory()->create([
        'user_id' => $this->user->id,
        'objective_id' => $objective->id,
        'date' => now()->toDateString(),
    ]);

    // Tomorrow's recommendation
    $tomorrowRec = \App\Models\DailyRecommendation::factory()->create([
        'user_id' => $this->user->id,
        'objective_id' => $objective->id,
        'date' => now()->addDay()->toDateString(),
    ]);

    $response = $this->actingAs($this->user)->get(route('objectives.show', $objective));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('objectives/show')
        ->has('objective.daily_recommendations', 2)
        ->where('objective.daily_recommendations.0.id', $todayRec->id) // Today first
        ->where('objective.daily_recommendations.1.id', $tomorrowRec->id) // Tomorrow second
    );
});

test('user can create an objective', function () {
    $response = $this->actingAs($this->user)->post(route('objectives.store'), [
        'type' => '10 km',
        'target_date' => now()->addMonths(3)->toDateString(),
        'description' => 'My first 10K',
        'running_days' => ['Monday', 'Wednesday', 'Friday'],
    ]);

    $response->assertRedirect(route('objectives.index'));
    $this->assertDatabaseHas('objectives', [
        'user_id' => $this->user->id,
        'type' => '10 km',
        'status' => 'active',
    ]);
});

test('creating a new objective abandons the current active one', function () {
    $oldObjective = Objective::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
    ]);

    $this->actingAs($this->user)->post(route('objectives.store'), [
        'type' => '21.1 km',
        'target_date' => now()->addMonths(6)->toDateString(),
        'running_days' => ['Tuesday', 'Thursday', 'Saturday'],
    ]);

    $oldObjective->refresh();
    expect($oldObjective->status)->toBe('abandoned');

    $this->assertDatabaseHas('objectives', [
        'user_id' => $this->user->id,
        'type' => '21.1 km',
        'status' => 'active',
    ]);
});

test('user can update an objective', function () {
    $objective = Objective::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->put(route('objectives.update', $objective), [
        'type' => '42.2 km',
        'target_date' => now()->addMonths(12)->toDateString(),
        'status' => 'completed',
        'running_days' => ['Monday', 'Tuesday', 'Wednesday'],
    ]);

    $response->assertRedirect(route('objectives.index'));
    $objective->refresh();
    expect($objective->type)->toBe('42.2 km');
    expect($objective->status)->toBe('completed');
});

test('user cannot update another users objective', function () {
    $otherUser = User::factory()->create();
    $objective = Objective::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($this->user)->put(route('objectives.update', $objective), [
        'type' => '5 km',
    ]);

    $response->assertStatus(403);
});

test('user can delete an objective', function () {
    $objective = Objective::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->delete(route('objectives.destroy', $objective));

    $response->assertRedirect(route('objectives.index'));
    $this->assertDatabaseMissing('objectives', ['id' => $objective->id]);
});

test('objective detail view includes running stats', function () {
    \Illuminate\Support\Facades\Queue::fake();
    
    $objective = Objective::factory()->create([
        'user_id' => $this->user->id,
    ]);

    // Create some activities for the user
    \App\Models\Activity::factory()->create([
        'user_id' => $this->user->id,
        'type' => 'Run',
        'distance' => 5000, // 5 km in meters
        'moving_time' => 1800, // 30 minutes
    ]);

    \App\Models\Activity::factory()->create([
        'user_id' => $this->user->id,
        'type' => 'Run',
        'distance' => 10000, // 10 km
        'moving_time' => 3000, // 50 minutes
    ]);

    $response = $this->actingAs($this->user)->get(route('objectives.show', $objective));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('objectives/show')
        ->has('objective')
        ->has('runningStats')
        ->where('runningStats.total_distance_km', 15)
        ->where('runningStats.total_runs', 2)
    );
});

test('running stats calculates best pace correctly', function () {
    \Illuminate\Support\Facades\Queue::fake();
    
    $objective = Objective::factory()->create([
        'user_id' => $this->user->id,
    ]);

    // Slower run: 10 min/km pace
    \App\Models\Activity::factory()->create([
        'user_id' => $this->user->id,
        'type' => 'Run',
        'distance' => 5000, // 5 km
        'moving_time' => 3000, // 50 minutes (600 sec/km)
    ]);

    // Faster run: 5 min/km pace
    \App\Models\Activity::factory()->create([
        'user_id' => $this->user->id,
        'type' => 'Run',
        'distance' => 5000, // 5 km
        'moving_time' => 1500, // 25 minutes (300 sec/km)
    ]);

    $response = $this->actingAs($this->user)->get(route('objectives.show', $objective));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('runningStats')
        ->where('runningStats.best_pace_per_km', '5:00 /km')
    );
});

test('running stats handles no activities gracefully', function () {
    $objective = Objective::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('objectives.show', $objective));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('runningStats')
        ->where('runningStats.total_distance_km', 0)
        ->where('runningStats.total_runs', 0)
        ->where('runningStats.best_pace_per_km', null)
    );
});
