<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $strava_id
 * @property string|null $strava_token
 * @property string|null $strava_refresh_token
 * @property \Illuminate\Support\Carbon|null $strava_token_expires_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $locale
 * @property int|null $age
 * @property float|null $weight_kg
 * @property string $fitness_level
 * @property string|null $injury_history
 * @property string|null $training_preferences
 * @property-read Objective|null $currentObjective
 */
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the user's preferred locale.
     */
    public function preferredLocale(): string
    {
        return $this->locale ?? config('app.locale');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'strava_id',
        'strava_token',
        'strava_refresh_token',
        'strava_token_expires_at',
        'locale',
        'age',
        'weight_kg',
        'fitness_level',
        'injury_history',
        'training_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'strava_token',
        'strava_refresh_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'strava_token_expires_at' => 'datetime',
        ];
    }

    /**
     * Get the activities for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Activity, $this>
     */
    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the objectives for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Objective, $this>
     */
    public function objectives(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Objective::class);
    }

    /**
     * Get the current active objective for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<Objective, $this>
     */
    public function currentObjective(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Objective::class)->where('status', 'active')->latestOfMany();
    }

    /**
     * Get the daily recommendations for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<DailyRecommendation, $this>
     */
    public function dailyRecommendations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DailyRecommendation::class);
    }

    /**
     * Get the workout feedback for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<WorkoutFeedback, $this>
     */
    public function workoutFeedback(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkoutFeedback::class);
    }

    /**
     * Get the personal records for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<PersonalRecord, $this>
     */
    public function personalRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PersonalRecord::class);
    }

    /**
     * Get the user's current activity streak.
     *
     * @return int Number of consecutive days with activity
     */
    public function getActivityStreak(): int
    {
        $streak = 0;
        $currentDate = now()->startOfDay();

        while (true) {
            $hasActivity = $this->activities()
                ->whereDate('start_date', $currentDate)
                ->exists();

            if (! $hasActivity) {
                break;
            }

            $streak++;
            $currentDate->subDay();
        }

        return $streak;
    }

    /**
     * Calculate current recovery score based on recent activities.
     *
     * @return float Recovery score from 0-10
     */
    public function getCurrentRecoveryScore(): float
    {
        // Get activities from last 7 days
        $recentActivities = $this->activities()
            ->where('start_date', '>=', now()->subDays(7))
            ->orderByDesc('start_date')
            ->get();

        if ($recentActivities->isEmpty()) {
            return 10.0; // Fully recovered if no recent activities
        }

        // Calculate fatigue based on intensity and recency
        $totalFatigue = 0;
        foreach ($recentActivities as $activity) {
            $daysAgo = now()->diffInDays($activity->start_date);
            $intensityScore = $activity->intensity_score ?? 5.0;

            // More recent activities contribute more to fatigue
            $recencyFactor = max(0, 1 - ($daysAgo / 7));
            $totalFatigue += ($intensityScore / 10) * $recencyFactor;
        }

        // Convert fatigue to recovery score (inverted)
        $recoveryScore = max(0, 10 - ($totalFatigue * 2));

        return round($recoveryScore, 1);
    }

    /**
     * Get global running statistics for the user.
     *
     * @return array<string, mixed>
     */
    public function getRunningStats(): array
    {
        $stats = $this->activities()
            ->where('type', 'Run')
            ->where('distance', '>', 0) // Exclude zero-distance activities
            ->selectRaw('
                COUNT(*) as total_runs,
                SUM(distance) as total_distance,
                SUM(moving_time) as total_time,
                MIN(moving_time / (distance / 1000)) as best_pace_seconds
            ')
            ->first();

        $totalDistance = $stats->total_distance ?? 0; // in meters
        $totalTime = $stats->total_time ?? 0; // in seconds
        $totalRuns = $stats->total_runs ?? 0;
        $bestPace = $stats->best_pace_seconds;

        // Get fastest run details
        $fastestRun = null;
        if ($bestPace) {
            $fastestRun = $this->activities()
                ->where('type', 'Run')
                ->where('distance', '>', 0)
                ->selectRaw('*, (moving_time / (distance / 1000)) as pace_seconds')
                ->orderBy('pace_seconds', 'ASC')
                ->first();
        }

        // Calculate average pace
        $averagePace = null;
        if ($totalDistance > 0) {
            $averagePace = $totalTime / ($totalDistance / 1000); // seconds per km
        }

        return [
            'total_distance_km' => round($totalDistance / 1000, 2),
            'total_time_seconds' => $totalTime,
            'total_time_formatted' => $this->formatTime((int) $totalTime),
            'total_runs' => $totalRuns,
            'average_pace_per_km' => $averagePace ? $this->formatPace($averagePace) : null,
            'best_pace_per_km' => $bestPace ? $this->formatPace($bestPace) : null,
            'fastest_run' => $fastestRun ? [
                'name' => $fastestRun->name,
                'distance_km' => round($fastestRun->distance / 1000, 2),
                'pace' => $this->formatPace(($fastestRun->moving_time / ($fastestRun->distance / 1000))),
                'date' => $fastestRun->start_date?->format('Y-m-d'),
            ] : null,
        ];
    }

    /**
     * Format time in seconds to human-readable format.
     */
    private function formatTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }

        return sprintf('%dm %ds', $minutes, $secs);
    }

    /**
     * Format pace (seconds per km) to min:sec format.
     */
    private function formatPace(float $secondsPerKm): string
    {
        $minutes = floor($secondsPerKm / 60);
        $seconds = round($secondsPerKm % 60);

        return sprintf('%d:%02d /km', $minutes, $seconds);
    }
}
