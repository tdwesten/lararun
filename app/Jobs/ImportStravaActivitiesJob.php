<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\User;
use App\Services\StravaApiService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportStravaActivitiesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(StravaApiService $stravaApiService): void
    {
        Log::info("Starting Strava activity import for user: {$this->user->id} ({$this->user->email})");

        $activities = $stravaApiService->getActivities($this->user, 30);

        if (empty($activities)) {
            Log::info("No activities found for user: {$this->user->id}");
            return;
        }

        $importedCount = 0;
        $skippedCount = 0;

        foreach ($activities as $stravaActivity) {
            // Only process running activities
            if ($stravaActivity->type !== 'Run') {
                $skippedCount++;
                continue;
            }

            $activity = Activity::where('strava_id', $stravaActivity->id)->first();

            if (!$activity) {
                Log::debug("Importing new Strava activity: {$stravaActivity->id} for user: {$this->user->id}");

                $detailedData = $stravaApiService->getActivityWithZones($this->user, $stravaActivity->id);

                $zoneData = $detailedData['zones'] ?? null;
                $flattenedZones = $this->parseZones($zoneData);

                Activity::create([
                    'user_id' => $this->user->id,
                    'strava_id' => $stravaActivity->id,
                    'name' => $stravaActivity->name,
                    'type' => $stravaActivity->type,
                    'distance' => $stravaActivity->distance,
                    'moving_time' => $stravaActivity->moving_time,
                    'elapsed_time' => $stravaActivity->elapsed_time,
                    'start_date' => Carbon::parse($stravaActivity->start_date),
                    'zone_data' => $zoneData,
                    'z1_time' => $flattenedZones['z1'],
                    'z2_time' => $flattenedZones['z2'],
                    'z3_time' => $flattenedZones['z3'],
                    'z4_time' => $flattenedZones['z4'],
                    'z5_time' => $flattenedZones['z5'],
                    'intensity_score' => $this->calculateIntensityScore($flattenedZones),
                    'zone_data_available' => !empty($zoneData),
                ]);

                $importedCount++;
            } else {
                $skippedCount++;
            }
        }

        Log::info("Finished Strava activity import for user: {$this->user->id}. Imported: {$importedCount}, Skipped/Existing: {$skippedCount}");
    }

    /**
     * Parse zone data from Strava API response.
     */
    protected function parseZones(?array $zoneData): array
    {
        $zones = ['z1' => 0, 'z2' => 0, 'z3' => 0, 'z4' => 0, 'z5' => 0];

        if (!$zoneData) {
            return $zones;
        }

        foreach ($zoneData as $zoneGroup) {
            if ($zoneGroup->type === 'heartrate') {
                $distribution = $zoneGroup->distribution_buckets;
                foreach ($distribution as $index => $bucket) {
                    $key = 'z' . ($index + 1);
                    if (isset($zones[$key])) {
                        $zones[$key] = $bucket->time;
                    }
                }
                break;
            }
        }

        return $zones;
    }

    /**
     * Calculate intensity score weighted by zones.
     */
    protected function calculateIntensityScore(array $zones): float
    {
        // Simple weighting: Z1=1, Z2=2, Z3=3, Z4=4, Z5=5
        // Normalized to some value, let's say per hour of Z1
        $score = ($zones['z1'] * 1) +
                 ($zones['z2'] * 2) +
                 ($zones['z3'] * 3) +
                 ($zones['z4'] * 4) +
                 ($zones['z5'] * 5);

        // Return score (can be divided by 60 for minutes or 3600 for hours to normalize)
        // Let's keep it as a raw weighted sum of seconds for now, or divide by 60 to get "points".
        return round($score / 60, 2);
    }
}
