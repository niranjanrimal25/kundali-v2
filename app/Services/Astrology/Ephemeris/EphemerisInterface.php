<?php

namespace App\Services\Astrology\Ephemeris;

use DateTimeImmutable;

/**
 * Contract for the astronomy layer.
 *
 * Implementations must return SIDEREAL longitudes already adjusted by the
 * configured ayanamsa. Keeping this behind an interface means the engine
 * (Swiss Ephemeris binary, PHP extension, or a pure-PHP VSOP87 fallback)
 * can be swapped without touching any Vedic calculation code.
 */
interface EphemerisInterface
{
    /**
     * @param  DateTimeImmutable  $utc  Birth moment in UTC
     * @param  float  $latitude  Decimal degrees, north positive
     * @param  float  $longitude  Decimal degrees, east positive
     * @return array{
     *     planets: array<string, array{longitude: float, speed: float, retrograde: bool}>,
     *     ascendant: float,
     *     midheaven: float,
     *     houses: array<int, float>,
     *     ayanamsa: float,
     *     julian_day: float
     * }
     */
    public function calculate(DateTimeImmutable $utc, float $latitude, float $longitude): array;
}
