<?php

namespace App\Services;

use App\Enums\RecordType;
use App\Models\Activity;
use App\Models\PersonalRecord;
use Illuminate\Support\Facades\Log;

class PersonalRecordService
{
    /**
     * Check and update personal records for an activity.
     */
    public function checkAndUpdateRecords(Activity $activity): void
    {
        if ($activity->type !== 'Run' || $activity->distance <= 0) {
            return;
        }

        $distanceKm = $activity->distance / 1000;
        $timeSeconds = $activity->moving_time;
        $pace = $timeSeconds / $distanceKm; // seconds per km

        // Check for distance-based records
        $this->checkDistanceRecord($activity, $distanceKm, $timeSeconds, 5, RecordType::Fastest5K);
        $this->checkDistanceRecord($activity, $distanceKm, $timeSeconds, 10, RecordType::Fastest10K);
        $this->checkDistanceRecord($activity, $distanceKm, $timeSeconds, 21.0975, RecordType::FastestHalfMarathon);
        $this->checkDistanceRecord($activity, $distanceKm, $timeSeconds, 42.195, RecordType::FastestMarathon);

        // Check for longest run
        $this->checkLongestRun($activity, $activity->distance);

        // Check for fastest pace (any distance over 1km)
        if ($distanceKm >= 1) {
            $this->checkFastestPace($activity, $pace);
        }
    }

    /**
     * Check if activity sets a new record for a specific distance.
     */
    private function checkDistanceRecord(
        Activity $activity,
        float $actualDistance,
        int $actualTime,
        float $targetDistance,
        RecordType $recordType
    ): void {
        // Allow 5% tolerance for distance matching
        $tolerance = $targetDistance * 0.05;

        if ($actualDistance < ($targetDistance - $tolerance) || $actualDistance > ($targetDistance + $tolerance)) {
            return;
        }

        $existingRecord = PersonalRecord::where('user_id', $activity->user_id)
            ->where('record_type', $recordType)
            ->first();

        if (! $existingRecord || $actualTime < $existingRecord->value) {
            PersonalRecord::updateOrCreate(
                [
                    'user_id' => $activity->user_id,
                    'record_type' => $recordType,
                ],
                [
                    'activity_id' => $activity->id,
                    'value' => $actualTime,
                    'achieved_date' => $activity->start_date?->toDateString() ?? now()->toDateString(),
                ]
            );

            Log::info("New personal record set: {$recordType} for user {$activity->user_id}");
        }
    }

    /**
     * Check if activity sets a new longest run record.
     */
    private function checkLongestRun(Activity $activity, float $distance): void
    {
        $existingRecord = PersonalRecord::where('user_id', $activity->user_id)
            ->where('record_type', RecordType::LongestRun)
            ->first();

        if (! $existingRecord || $distance > $existingRecord->value) {
            PersonalRecord::updateOrCreate(
                [
                    'user_id' => $activity->user_id,
                    'record_type' => RecordType::LongestRun,
                ],
                [
                    'activity_id' => $activity->id,
                    'value' => $distance,
                    'achieved_date' => $activity->start_date?->toDateString() ?? now()->toDateString(),
                ]
            );

            Log::info("New longest run record set for user {$activity->user_id}: {$distance}m");
        }
    }

    /**
     * Check if activity sets a new fastest pace record.
     */
    private function checkFastestPace(Activity $activity, float $pace): void
    {
        $existingRecord = PersonalRecord::where('user_id', $activity->user_id)
            ->where('record_type', RecordType::FastestPace)
            ->first();

        if (! $existingRecord || $pace < $existingRecord->value) {
            PersonalRecord::updateOrCreate(
                [
                    'user_id' => $activity->user_id,
                    'record_type' => RecordType::FastestPace,
                ],
                [
                    'activity_id' => $activity->id,
                    'value' => $pace,
                    'achieved_date' => $activity->start_date?->toDateString() ?? now()->toDateString(),
                ]
            );

            Log::info("New fastest pace record set for user {$activity->user_id}: {$pace}s/km");
        }
    }
}
