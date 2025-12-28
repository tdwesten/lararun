<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\ImportStravaActivitiesJob;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class StravaController extends Controller
{
    /**
     * Show the Strava connection page.
     */
    public function connect(): Response|RedirectResponse
    {
        if (Auth::user()->strava_token) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('auth/strava-connect');
    }

    /**
     * Redirect the user to the Strava authentication page.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('strava');

        return $driver
            ->scopes(['activity:read', 'activity:read_all'])
            ->redirect();
    }

    /**
     * Obtain the user information from Strava.
     */
    public function callback(): RedirectResponse
    {
        try {
            /** @var \Laravel\Socialite\Two\User $stravaUser */
            $stravaUser = Socialite::driver('strava')->user();
        } catch (\Exception $e) {
            return redirect()->route('login');
        }

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            // Check if this Strava ID is already taken by another user
            $existingUser = User::where('strava_id', $stravaUser->getId())->where('id', '!=', $user->id)->first();
            if ($existingUser) {
                return redirect()->route('auth.strava.connect')->with('error', 'This Strava account is already connected to another user.');
            }

            $user->strava_id = $stravaUser->getId();
        } else {
            $user = User::firstOrNew([
                'strava_id' => $stravaUser->getId(),
            ]);
        }

        $user->fill([
            'name' => $stravaUser->getName(),
            'strava_token' => $stravaUser->token,
            'strava_refresh_token' => $stravaUser->refreshToken,
            'strava_token_expires_at' => now()->addSeconds($stravaUser->expiresIn),
        ]);

        if (! $user->email && $stravaUser->getEmail()) {
            $user->email = $stravaUser->getEmail();
        }

        $user->save();

        if ($user->email && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        if (! Auth::check()) {
            Auth::login($user);
        }

        ImportStravaActivitiesJob::dispatch($user);

        return redirect()->intended(route('dashboard'));
    }
}
