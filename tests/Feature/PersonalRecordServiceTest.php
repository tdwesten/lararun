<?php

use App\Enums\RecordType;
use App\Models\Activity;
use App\Models\User;
use App\Services\PersonalRecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

beforeEach(function () {
    Bus::fake();
});

it('detects a new 5k personal record', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create([
        'user_id' => $user->id,
        'type' => 'Run',
        'distance' => 5000,
        'moving_time' => 1200, // 20:00
        'start_date' => now(),
    ]);

    $service = new PersonalRecordService;
    $service->checkAndUpdateRecords($activity);

    $this->assertDatabaseHas('personal_records', [
        'user_id' => $user->id,
        'record_type' => RecordType::Fastest5K->value,
        'value' => 1200,
        'activity_id' => $activity->id,
    ]);
});

it('updates a personal record if a better one is achieved', function () {
    $user = User::factory()->create();

    // Initial record
    $activity1 = Activity::factory()->create([
        'user_id' => $user->id,
        'type' => 'Run',
        'distance' => 5000,
        'moving_time' => 1500, // 25:00
    ]);

    $service = new PersonalRecordService;
    $service->checkAndUpdateRecords($activity1);

    $this->assertDatabaseHas('personal_records', [
        'user_id' => $user->id,
        'record_type' => RecordType::Fastest5K->value,
        'value' => 1500,
    ]);

    // Better record
    $activity2 = Activity::factory()->create([
        'user_id' => $user->id,
        'type' => 'Run',
        'distance' => 5010, // still counts as 5k within 5% tolerance
        'moving_time' => 1200, // 20:00
    ]);

    $service->checkAndUpdateRecords($activity2);

    $this->assertDatabaseHas('personal_records', [
        'user_id' => $user->id,
        'record_type' => RecordType::Fastest5K->value,
        'value' => 1200,
        'activity_id' => $activity2->id,
    ]);
});

it('detects fastest pace record', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create([
        'user_id' => $user->id,
        'type' => 'Run',
        'distance' => 2000, // 2km
        'moving_time' => 480, // 4:00/km = 240s/km
    ]);

    $service = new PersonalRecordService;
    $service->checkAndUpdateRecords($activity);

    $this->assertDatabaseHas('personal_records', [
        'user_id' => $user->id,
        'record_type' => RecordType::FastestPace->value,
        'value' => 240,
    ]);
});
