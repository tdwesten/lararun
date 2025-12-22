<?php

use App\Jobs\ImportStravaActivitiesJob;
use App\Models\User;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    User::whereNotNull('strava_token')->each(function (User $user) {
        ImportStravaActivitiesJob::dispatch($user);
    });
})->hourly()->description('Import Strava activities for users');

Schedule::command('app:generate-daily-training-plans')->dailyAt('08:00')->description('Generate daily training plans for users');
