<?php

use App\Http\Controllers\Auth\EmailEntryController;
use App\Http\Controllers\Auth\StravaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('auth/strava', [StravaController::class, 'redirect'])->name('auth.strava.redirect');
Route::get('auth/strava/callback', [StravaController::class, 'callback'])->name('auth.strava.callback');

Route::middleware('auth')->group(function () {
    Route::get('auth/email', [EmailEntryController::class, 'show'])->name('auth.email.show');
    Route::post('auth/email', [EmailEntryController::class, 'store'])->name('auth.email.store');
});

Route::middleware(['auth', 'email.set', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
