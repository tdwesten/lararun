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

Route::get('/privacy', function () {
    return Inertia::render('privacy');
})->name('privacy');

Route::get('auth/strava', [StravaController::class, 'redirect'])->name('auth.strava.redirect');
Route::get('auth/strava/callback', [StravaController::class, 'callback'])->name('auth.strava.callback');

Route::middleware('auth')->group(function () {
    Route::get('auth/email', [EmailEntryController::class, 'show'])->name('auth.email.show');
    Route::post('auth/email', [EmailEntryController::class, 'store'])->name('auth.email.store');
    Route::get('auth/strava/connect', [StravaController::class, 'connect'])->name('auth.strava.connect');
});

Route::middleware(['auth', 'email.set', 'verified', 'strava.connected'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        // Get trend data for last 7 days
        $chartData = \Flowframe\Trend\Trend::model(Activity::class)
            ->between(
                start: now()->subDays(6)->startOfDay(),
                end: now()->endOfDay(),
            )
            ->perDay()
            ->count()
            ->map(function ($item) use ($request) {
                // Get activities for this date
                $activities = Activity::where('user_id', $request->user()->id)
                    ->whereDate('start_date', $item->date)
                    ->where('type', 'Run')
                    ->get();
                
                return [
                    'date' => $item->date,
                    'count' => $activities->count(),
                    'distance' => round($activities->sum('distance') / 1000, 2), // Convert to km
                ];
            });

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
            'activityStreak' => $request->user()->getActivityStreak(),
            'recoveryScore' => $request->user()->getCurrentRecoveryScore(),
            'personalRecords' => $request->user()->personalRecords()
                ->with('activity:id,name')
                ->latest('achieved_date')
                ->limit(6)
                ->get(),
            'chartData' => $chartData,
        ]);
    })->name('dashboard');

    Route::resource('objectives', ObjectiveController::class);
    Route::post('objectives/{objective}/enhance-trainings', [ObjectiveController::class, 'enhanceTrainings'])->name('objectives.enhance-trainings');
    Route::get('activities', [\App\Http\Controllers\ActivityController::class, 'index'])->name('activities.index');
    Route::get('activities/{activity}', [\App\Http\Controllers\ActivityController::class, 'show'])->name('activities.show');
    Route::post('api/workout-feedback/{recommendation}', [\App\Http\Controllers\WorkoutFeedbackController::class, 'store'])->name('workout-feedback.store');
});

require __DIR__.'/settings.php';
