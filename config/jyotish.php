<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Swiss Ephemeris
    |--------------------------------------------------------------------------
    | Path to the compiled `swetest` binary and the directory holding the
    | .se1 ephemeris data files. Both ship with the repository so the app
    | works out of the box with zero external services.
    */

    'swetest_path' => env('SWETEST_PATH') ?: base_path('bin/swetest'),

    'ephe_path' => env('SWETEST_EPHE_PATH') ?: base_path('ephe'),

    /*
    |--------------------------------------------------------------------------
    | Ayanamsa
    |--------------------------------------------------------------------------
    | Maps to swetest's -sid<n> flag. Lahiri (Chitrapaksha) is the standard
    | for Nepali and Indian Parashari astrology.
    */

    'ayanamsa' => env('JYOTISH_AYANAMSA', 'lahiri'),

    'ayanamsa_codes' => [
        'lahiri' => 1,
        'raman' => 3,
        'krishnamurti' => 5,
        'fagan_bradley' => 0,
        'yukteshwar' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | House System
    |--------------------------------------------------------------------------
    | W = Whole Sign (classical Parashari, our default)
    | P = Placidus, K = Koch, E = Equal
    */

    'house_system' => env('JYOTISH_HOUSE_SYSTEM', 'W'),

    /*
    |--------------------------------------------------------------------------
    | Lunar Node
    |--------------------------------------------------------------------------
    | 'true' uses the True Node (oscillating), 'mean' uses the Mean Node.
    | Most Vedic software defaults to Mean; True is astronomically exact.
    */

    'node' => env('JYOTISH_NODE', 'true'),

    /*
    |--------------------------------------------------------------------------
    | Combustion Orbs (degrees from the Sun)
    |--------------------------------------------------------------------------
    | Classical Parashari values. Retrograde planets use tighter orbs.
    */

    'combustion_orbs' => [
        'Moon' => 12.0,
        'Mars' => 17.0,
        'Mercury' => 14.0,
        'Mercury_retro' => 12.0,
        'Jupiter' => 11.0,
        'Venus' => 10.0,
        'Venus_retro' => 8.0,
        'Saturn' => 15.0,
    ],

];
