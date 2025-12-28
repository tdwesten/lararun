<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStravaIsConnected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() &&
            ! $request->user()->strava_token &&
            ! $request->is('auth/strava*') &&
            ! $request->is('logout')) {
            return redirect()->route('auth.strava.connect');
        }

        return $next($request);
    }
}
