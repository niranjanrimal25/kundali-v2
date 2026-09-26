<?php

namespace App\Providers;

use App\Services\Astrology\Ephemeris\EphemerisInterface;
use App\Services\Astrology\Ephemeris\SwissEphemeris;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The astronomy engine is bound behind an interface so it can be
        // swapped (pure-PHP fallback, or a fake in tests) without any
        // change to the Vedic calculation layer.
        $this->app->bind(EphemerisInterface::class, SwissEphemeris::class);
    }

    public function boot(): void
    {
        //
    }
}
