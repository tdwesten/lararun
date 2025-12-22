<?php

namespace App\Providers;

use App\Models\Activity;
use App\Observers\ActivityObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->alias('Strava', \CodeToad\Strava\Strava::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('strava', \SocialiteProviders\Strava\Provider::class);
        });

        Activity::observe(ActivityObserver::class);
    }
}
