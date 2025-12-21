<?php

use App\Jobs\ImportStravaActivitiesJob;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    User::whereNotNull('strava_token')->each(function (User $user) {
        ImportStravaActivitiesJob::dispatch($user);
    });
})->hourly()->description('Import Strava activities for users');
