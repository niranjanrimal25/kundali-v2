<?php

namespace App\Services\Astrology\Ephemeris;

use DateTimeImmutable;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Swiss Ephemeris driver.
 *
 * Shells out to the bundled `swetest` binary, which is accurate to roughly
 * one arc-second for the visible planets across the supported date range.
 * Swiss Ephemeris is free software (AGPL) — no API keys, no subscription.
 *
 * Output is requested in a fixed, machine-friendly format:
 *   -fPls  → name, longitude, speed
 *   -g,    → comma separated
 *   -head  → suppress the human-readable header block
 */
class SwissEphemeris implements EphemerisInterface
{
    /**
     * swetest planet selector characters.
     * 0=Sun 1=Moon 2=Mercury 3=Venus 4=Mars 5=Jupiter 6=Saturn
     * t=True Node, m=Mean Node
     */
    private const PLANET_SELECTOR = '0123456';

    /** Maps swetest's output labels onto our internal planet names. */
    private const LABEL_MAP = [
        'Sun' => 'Sun',
        'Moon' => 'Moon',
        'Mercury' => 'Mercury',
        'Venus' => 'Venus',
        'Mars' => 'Mars',
        'Jupiter' => 'Jupiter',
        'Saturn' => 'Saturn',
        'true Node' => 'Rahu',
        'mean Node' => 'Rahu',
    ];

    public function __construct(
        private readonly ?string $binary = null,
        private readonly ?string $ephePath = null,
    ) {}

    public function calculate(DateTimeImmutable $utc, float $latitude, float $longitude): array
    {
        $binary = $this->binary ?? config('jyotish.swetest_path');
        $ephe = $this->ephePath ?? config('jyotish.ephe_path');

        if (! is_executable($binary)) {
            throw new RuntimeException("Swiss Ephemeris binary not executable at: {$binary}");
        }

        $nodeChar = config('jyotish.node') === 'mean' ? 'm' : 't';
        $ayanamsaCode = config('jyotish.ayanamsa_codes.'.config('jyotish.ayanamsa'), 1);
        $houseSystem = config('jyotish.house_system', 'W');

        $args = [
            $binary,
            '-b'.$utc->format('j.n.Y'),
            '-ut'.$utc->format('H:i:s'),
            '-p'.self::PLANET_SELECTOR.$nodeChar,
            '-sid'.$ayanamsaCode,
            sprintf('-house%.6f,%.6f,%s', $longitude, $latitude, $houseSystem),
            '-fPls',
            '-g,',
            '-head',
            '-eswe',
            '-edir'.$ephe,
        ];

        $result = Process::timeout(20)->run($args);

        if (! $result->successful()) {
            throw new RuntimeException('swetest failed: '.$result->errorOutput());
        }

        return $this->parse($result->output(), $utc, $ayanamsaCode, $ephe, $binary);
    }

    private function parse(string $output, DateTimeImmutable $utc, int $ayanamsaCode, string $ephe, string $binary): array
    {
        $planets = [];
        $houses = [];
        $ascendant = null;
        $midheaven = null;

        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if ($line === '' || ! str_contains($line, ',')) {
                continue;
            }

            $parts = array_map('trim', explode(',', $line));
            $label = trim($parts[0]);

            // swetest emits an error line rather than a non-zero exit code
            // when ephemeris files are missing — catch that explicitly.
            if (str_starts_with($label, 'illegal') || str_starts_with($label, 'error')) {
                throw new RuntimeException('swetest error: '.$line);
            }

            if (! isset($parts[1]) || ! is_numeric($parts[1])) {
                continue;
            }

            $longitude = (float) $parts[1];
            $speed = isset($parts[2]) && is_numeric($parts[2]) ? (float) $parts[2] : 0.0;

            if (isset(self::LABEL_MAP[$label])) {
                $name = self::LABEL_MAP[$label];
                $planets[$name] = [
                    'longitude' => $longitude,
                    'speed' => $speed,
                    // Rahu/Ketu are always retrograde in Vedic convention.
                    'retrograde' => $name === 'Rahu' ? true : $speed < 0,
                ];

                continue;
            }

            if (preg_match('/^house\s+(\d+)$/', $label, $m)) {
                $houses[(int) $m[1]] = $longitude;

                continue;
            }

            if ($label === 'Ascendant') {
                $ascendant = $longitude;
            } elseif ($label === 'MC') {
                $midheaven = $longitude;
            }
        }

        if ($ascendant === null) {
            throw new RuntimeException('swetest returned no Ascendant. Raw output: '.substr($output, 0, 500));
        }

        foreach (['Sun', 'Moon', 'Mars', 'Mercury', 'Jupiter', 'Venus', 'Saturn', 'Rahu'] as $required) {
            if (! isset($planets[$required])) {
                throw new RuntimeException("swetest returned no position for {$required}.");
            }
        }

        // Ketu is always exactly 180° from Rahu.
        $planets['Ketu'] = [
            'longitude' => fmod($planets['Rahu']['longitude'] + 180.0, 360.0),
            'speed' => $planets['Rahu']['speed'],
            'retrograde' => true,
        ];

        return [
            'planets' => $planets,
            'ascendant' => $ascendant,
            'midheaven' => $midheaven ?? 0.0,
            'houses' => $houses,
            'ayanamsa' => $this->ayanamsaValue($utc, $ayanamsaCode, $ephe, $binary),
            'julian_day' => $this->julianDay($utc),
        ];
    }

    /** Query the ayanamsa value used, for display and verification. */
    private function ayanamsaValue(DateTimeImmutable $utc, int $code, string $ephe, string $binary): float
    {
        $result = Process::timeout(15)->run([
            $binary,
            '-b'.$utc->format('j.n.Y'),
            '-ut'.$utc->format('H:i:s'),
            '-p0',
            '-sid'.$code,
            '-fPl',
            '-eswe',
            '-edir'.$ephe,
        ]);

        if (preg_match('/ayanamsa\s*=\s*(\d+)°(\d+)\'([\d.]+)/u', $result->output(), $m)) {
            return (float) $m[1] + ((float) $m[2] / 60) + ((float) $m[3] / 3600);
        }

        return 0.0;
    }

    /** Julian Day (UT) — the canonical astronomical timestamp. */
    public function julianDay(DateTimeImmutable $utc): float
    {
        $year = (int) $utc->format('Y');
        $month = (int) $utc->format('n');
        $day = (int) $utc->format('j');

        $hour = (int) $utc->format('G')
            + ((int) $utc->format('i') / 60)
            + ((int) $utc->format('s') / 3600);

        if ($month <= 2) {
            $year--;
            $month += 12;
        }

        $a = intdiv($year, 100);
        // Gregorian calendar correction, valid from 1582-10-15 onward.
        $b = 2 - $a + intdiv($a, 4);

        return floor(365.25 * ($year + 4716))
            + floor(30.6001 * ($month + 1))
            + $day + $b - 1524.5
            + ($hour / 24);
    }
}
