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
        $activities = $this->activities()
            ->where('type', 'Run')
            ->get();

        $totalDistance = $activities->sum('distance'); // in meters
        $totalTime = $activities->sum('moving_time'); // in seconds
        $totalRuns = $activities->count();

        // Calculate best pace (lowest time per km)
        $bestPace = null;
        $fastestRun = null;
        
        foreach ($activities as $activity) {
            if ($activity->distance > 0) {
                $paceInSecondsPerKm = ($activity->moving_time / ($activity->distance / 1000));
                
                if ($bestPace === null || $paceInSecondsPerKm < $bestPace) {
                    $bestPace = $paceInSecondsPerKm;
                    $fastestRun = $activity;
                }
            }
        }

        // Calculate average pace
        $averagePace = null;
        if ($totalDistance > 0) {
            $averagePace = $totalTime / ($totalDistance / 1000); // seconds per km
        }

        return [
            'total_distance_km' => round($totalDistance / 1000, 2),
            'total_time_seconds' => $totalTime,
            'total_time_formatted' => $this->formatTime($totalTime),
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
