<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Activity, \App\Models\User>
     */
    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the objectives for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Objective, \App\Models\User>
     */
    public function objectives(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Objective::class);
    }

    /**
     * Get the current active objective for the user.
     */
    public function currentObjective(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Objective::class)->where('status', 'active')->latestOfMany();
    }

    /**
     * Get the daily recommendations for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\DailyRecommendation, \App\Models\User>
     */
    public function dailyRecommendations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DailyRecommendation::class);
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
                'date' => $fastestRun->start_date->format('Y-m-d'),
            ] : null,
        ];
    }

    /**
     * Format time in seconds to human-readable format.
     *
     * @param int $seconds
     * @return string
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
     *
     * @param float $secondsPerKm
     * @return string
     */
    private function formatPace(float $secondsPerKm): string
    {
        $minutes = floor($secondsPerKm / 60);
        $seconds = round($secondsPerKm % 60);
        
        return sprintf('%d:%02d /km', $minutes, $seconds);
    }
}
