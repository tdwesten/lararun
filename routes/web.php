<?php

use App\Http\Controllers\Auth\EmailEntryController;
use App\Http\Controllers\Auth\StravaController;
use App\Http\Controllers\ObjectiveController;
use App\Models\Activity;
use Illuminate\Http\Request;
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
    Route::get('dashboard', function (Request $request) {
        return Inertia::render('dashboard', [
            'activities' => Activity::query()
                ->where('user_id', $request->user()->id)
                ->latest('start_date')
                ->limit(10)
                ->get(),
            'currentObjective' => $request->user()->currentObjective,
            'todayRecommendation' => $request->user()->dailyRecommendations()
                ->whereDate('date', now()->toDateString())
                ->first(),
        ]);
    })->name('dashboard');

    Route::resource('objectives', ObjectiveController::class);
});

require __DIR__.'/settings.php';
