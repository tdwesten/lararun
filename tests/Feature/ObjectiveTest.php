<?php

use App\Models\Objective;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
});

test('user can see objectives index', function () {
    $response = $this->actingAs($this->user)->get(route('objectives.index'));

    $response->assertStatus(200);
});

test('user can create an objective', function () {
    $response = $this->actingAs($this->user)->post(route('objectives.store'), [
        'type' => '10 km',
        'target_date' => now()->addMonths(3)->toDateString(),
        'description' => 'My first 10K',
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
