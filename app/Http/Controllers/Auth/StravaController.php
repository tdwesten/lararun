<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\ImportStravaActivitiesJob;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class StravaController extends Controller
{
    /**
     * Redirect the user to the Strava authentication page.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('strava')
            ->scopes(['activity:read', 'activity:read_all'])
            ->redirect();
    }

    /**
     * Obtain the user information from Strava.
     */
    public function callback(): RedirectResponse
    {
        try {
            $stravaUser = Socialite::driver('strava')->user();
        } catch (\Exception $e) {
            return redirect()->route('login');
        }

        $user = User::firstOrNew([
            'strava_id' => $stravaUser->getId(),
        ]);

        $user->fill([
            'name' => $stravaUser->getName(),
            'strava_token' => $stravaUser->token,
            'strava_refresh_token' => $stravaUser->refreshToken,
            'strava_token_expires_at' => now()->addSeconds($stravaUser->expiresIn),
        ]);

        if ($stravaUser->getEmail()) {
            $user->email = $stravaUser->getEmail();
        }

        $user->save();

        if ($user->email && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        Auth::login($user);

        ImportStravaActivitiesJob::dispatch($user);

        return redirect()->intended(route('dashboard'));
    }
}
