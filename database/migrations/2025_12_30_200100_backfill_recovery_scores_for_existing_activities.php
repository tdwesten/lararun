<?php

use App\Models\Activity;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update recovery scores for existing activities with intensity scores
        Activity::where('type', 'Run')
            ->whereNotNull('intensity_score')
            ->whereNull('recovery_score')
            ->chunk(100, function ($activities) {
                foreach ($activities as $activity) {
                    // Calculate recovery based on intensity score
                    // Higher intensity = lower immediate recovery score
                    $recoveryScore = max(0, 10 - $activity->intensity_score);

                    // Estimate recovery hours based on intensity and distance
                    $distanceKm = $activity->distance / 1000;
                    $baseRecoveryHours = $distanceKm * 2; // 2 hours per km as baseline
                    $intensityMultiplier = $activity->intensity_score / 5; // 1-2x based on intensity
                    $estimatedRecoveryHours = (int) round($baseRecoveryHours * $intensityMultiplier);

                    $activity->update([
                        'recovery_score' => $recoveryScore,
                        'estimated_recovery_hours' => $estimatedRecoveryHours,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set recovery fields back to null
        Activity::whereNotNull('recovery_score')
            ->update([
                'recovery_score' => null,
                'estimated_recovery_hours' => null,
            ]);
    }
};
