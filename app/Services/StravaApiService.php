<?php

namespace App\Services;

use App\Models\User;
use CodeToad\Strava\Strava;
use Illuminate\Support\Facades\Log;

class StravaApiService
{
    public function __construct(protected Strava $strava)
    {
    }

    /**
     * Get activities for a user.
     *
     * @param  User  $user
     * @param  int  $perPage
     * @return array
     */
    public function getActivities(User $user, int $perPage = 30): array
    {
        $token = $this->getValidToken($user);

        if (!$token) {
            return [];
        }

        try {
            return $this->strava->activities($token, 1, $perPage);
        } catch (\Exception $e) {
            Log::error('Strava API Error (getActivities): ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get detailed activity with zones.
     *
     * @param  User  $user
     * @param  string  $stravaActivityId
     * @return array
     */
    public function getActivityWithZones(User $user, string $stravaActivityId): array
    {
        $token = $this->getValidToken($user);

        if (!$token) {
            return [];
        }

        try {
            $activity = $this->strava->activity($token, $stravaActivityId);
            $zones = $this->strava->activityZones($token, $stravaActivityId);

            return [
                'activity' => $activity,
                'zones' => $zones,
            ];
        } catch (\Exception $e) {
            Log::error('Strava API Error (getActivityWithZones): ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ensure we have a valid access token.
     *
     * @param  User  $user
     * @return string|null
     */
    protected function getValidToken(User $user): ?string
    {
        if (!$user->strava_token || !$user->strava_refresh_token) {
            return null;
        }

        // Refresh token if it expires in the next 5 minutes
        if (!$user->strava_token_expires_at || $user->strava_token_expires_at->isPast() || now()->diffInMinutes($user->strava_token_expires_at, false) < 5) {
            return $this->refreshToken($user);
        }

        return $user->strava_token;
    }

    /**
     * Refresh the access token.
     *
     * @param  User  $user
     * @return string|null
     */
    protected function refreshToken(User $user): ?string
    {
        try {
            $response = $this->strava->refreshToken($user->strava_refresh_token);

            if (isset($response->access_token)) {
                $user->update([
                    'strava_token' => $response->access_token,
                    'strava_refresh_token' => $response->refresh_token,
                    'strava_token_expires_at' => now()->addSeconds($response->expires_in),
                ]);

                return $response->access_token;
            }
        } catch (\Exception $e) {
            Log::error('Strava Token Refresh Error: ' . $e->getMessage());
        }

        return null;
    }
}
