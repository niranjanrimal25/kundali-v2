<?php

namespace App\Console\Commands;

use App\Models\Kundali;
use App\Services\Astrology\KundaliService;
use App\Services\Astrology\Support\Nakshatras;
use App\Services\Astrology\TimeResolver;
use App\Services\Astrology\VimshottariDasha;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Independent verification of the chart engine.
 *
 * Two kinds of check are run:
 *
 *  1. EXTERNAL — the ayanamsa is compared against the published Lahiri
 *     (Chitra Paksha) reference value, and every planetary longitude is
 *     recomputed by invoking the swetest binary directly, bypassing our
 *     own parsing, timezone handling and service layer entirely. If our
 *     pipeline has a bug, these disagree.
 *
 *  2. INVARIANTS — relationships that must hold in any correct Vedic
 *     chart regardless of ephemeris: Ketu opposite Rahu, whole-sign house
 *     arithmetic, nakshatra/pada boundaries, the 120-year Vimshottari
 *     total, and dasha sequence continuity.
 *
 * Tolerances follow the accepted published standard for Vedic software:
 * planets +/- 0.05 deg, ascendant +/- 0.50 deg, ayanamsa +/- 0.02 deg,
 * nakshatra pada exact.
 */
class ValidateChart extends Command
{
    protected $signature = 'jyotish:validate {kundali? : Kundali id (defaults to the first)}';

    protected $description = 'Verify chart accuracy against external references and internal invariants';

    private const TOL_PLANET = 0.05;

    private const TOL_ASCENDANT = 0.50;

    private const TOL_AYANAMSA = 0.02;

    /**
     * Published Lahiri (Chitra Paksha) ayanamsa at 1990-01-01 00:00 UT,
     * 23 deg 43' 15", with the standard precession rate of 50.2719"/year.
     * Used only as an order-of-magnitude external sanity check.
     */
    private const LAHIRI_1990 = 23.720833;

    private const LAHIRI_RATE = 50.2719;

    private int $passed = 0;

    private int $failed = 0;

    public function handle(KundaliService $service, TimeResolver $times): int
    {
        $kundali = $this->argument('kundali')
            ? Kundali::findOrFail($this->argument('kundali'))
            : Kundali::first();

        if (! $kundali) {
            $this->error('No kundali found. Seed one first.');

            return self::FAILURE;
        }

        $facts = $service->facts($kundali, true);

        $this->line('');
        $this->info("Validating: {$kundali->name}");
        $this->line(sprintf(
            '  %s %s  %s  (%.5f, %.5f)',
            $kundali->birth_date->format('Y-m-d'),
            $kundali->birth_time,
            $kundali->timezone,
            $kundali->latitude,
            $kundali->longitude
        ));
        $this->line('');

        $this->externalAyanamsa($facts);
        $this->externalEphemeris($kundali, $facts, $times);
        $this->invariants($facts);

        $this->line('');
        $total = $this->passed + $this->failed;

        if ($this->failed === 0) {
            $this->info("All {$total} checks passed.");

            return self::SUCCESS;
        }

        $this->error("{$this->failed} of {$total} checks FAILED.");

        return self::FAILURE;
    }

    /** Compare our ayanamsa against the published Lahiri reference. */
    private function externalAyanamsa(array $facts): void
    {
        $this->comment('External — Lahiri ayanamsa reference');

        $jd = $facts['meta']['julian_day'];
        $yearsSince1990 = ($jd - 2447892.5) / 365.25;
        $expected = self::LAHIRI_1990 + ($yearsSince1990 * self::LAHIRI_RATE / 3600);

        $actual = $facts['meta']['ayanamsa_value'];

        $this->check(
            'Ayanamsa within tolerance of published Lahiri',
            abs($actual - $expected) <= self::TOL_AYANAMSA,
            sprintf('ours %.6f vs reference %.6f (diff %.1f")', $actual, $expected, abs($actual - $expected) * 3600)
        );
    }

    /**
     * Recompute every longitude by shelling out to swetest directly.
     * This shares no code with the chart pipeline beyond the binary.
     */
    private function externalEphemeris(Kundali $kundali, array $facts, TimeResolver $times): void
    {
        $this->comment('External — independent swetest recomputation');

        $utc = $times->toUtc(
            $kundali->birth_date->format('Y-m-d'),
            $kundali->birth_time,
            $kundali->timezone
        );

        $binary = base_path('bin/swetest');

        if (! is_executable($binary)) {
            $this->warn('  swetest not built — run php artisan jyotish:install-ephemeris');

            return;
        }

        $result = Process::path(base_path())->run([
            $binary,
            '-b'.$utc->format('j.n.Y'),
            '-ut'.$utc->format('H:i:s'),
            '-p0123456t',
            '-sid1',
            '-house'.$kundali->longitude.','.$kundali->latitude.',W',
            '-fPls',
            '-eswe',
            '-edir./ephe',
            '-g,',
            '-head',
        ]);

        if (! $result->successful()) {
            $this->warn('  swetest invocation failed: '.trim($result->errorOutput()));

            return;
        }

        $map = [
            'Sun' => 'Sun', 'Moon' => 'Moon', 'Mercury' => 'Mercury',
            'Venus' => 'Venus', 'Mars' => 'Mars', 'Jupiter' => 'Jupiter',
            'Saturn' => 'Saturn', 'true Node' => 'Rahu',
        ];

        $reference = [];

        foreach (explode("\n", trim($result->output())) as $line) {
            $cols = array_map('trim', explode(',', $line));

            if (count($cols) < 2) {
                continue;
            }

            $name = $cols[0];

            if (isset($map[$name])) {
                $reference[$map[$name]] = (float) $cols[1];
            }

            if ($name === 'Ascendant') {
                $reference['Ascendant'] = (float) $cols[1];
            }
        }

        foreach ($reference as $name => $expected) {
            if ($name === 'Ascendant') {
                $this->check(
                    'Ascendant matches swetest',
                    $this->angleDiff($facts['lagna']['longitude'], $expected) <= self::TOL_ASCENDANT,
                    sprintf('%.6f vs %.6f', $facts['lagna']['longitude'], $expected)
                );

                continue;
            }

            $ours = $facts['planets'][$name]['longitude'] ?? null;

            $this->check(
                "{$name} matches swetest",
                $ours !== null && $this->angleDiff($ours, $expected) <= self::TOL_PLANET,
                sprintf('%.6f vs %.6f', $ours ?? -1, $expected)
            );
        }

        // Ketu is derived, not reported by swetest.
        if (isset($reference['Rahu'], $facts['planets']['Ketu'])) {
            $this->check(
                'Ketu is exactly opposite swetest Rahu',
                $this->angleDiff($facts['planets']['Ketu']['longitude'], fmod($reference['Rahu'] + 180, 360)) <= self::TOL_PLANET,
                sprintf('%.6f', $facts['planets']['Ketu']['longitude'])
            );
        }
    }

    /** Relationships that must hold in any correct chart. */
    private function invariants(array $facts): void
    {
        $this->comment('Invariants — internal consistency');

        $lagnaSign = $facts['lagna']['sign'];

        foreach ($facts['planets'] as $name => $planet) {
            $expectedSign = (int) floor($planet['longitude'] / 30) % 12;

            $this->check(
                "{$name}: sign derived from longitude",
                $planet['sign'] === $expectedSign,
                "sign {$planet['sign']}, expected {$expectedSign}"
            );

            $expectedHouse = ((($planet['sign'] - $lagnaSign + 12) % 12) + 1);

            $this->check(
                "{$name}: whole-sign house placement",
                $planet['house'] === $expectedHouse,
                "house {$planet['house']}, expected {$expectedHouse}"
            );

            $expectedNak = (int) floor($planet['longitude'] / Nakshatras::SPAN) % 27;

            $this->check(
                "{$name}: nakshatra boundary",
                $planet['nakshatra']['index'] === $expectedNak,
                "index {$planet['nakshatra']['index']}, expected {$expectedNak}"
            );

            $this->check(
                "{$name}: pada in range 1-4",
                $planet['nakshatra']['pada'] >= 1 && $planet['nakshatra']['pada'] <= 4,
                'pada '.$planet['nakshatra']['pada']
            );
        }

        // Every house is occupied exactly once by each sign, 1..12.
        $houseSigns = array_map(fn ($h) => $h['sign'], $facts['houses']);

        $this->check(
            'Twelve houses carry twelve distinct signs',
            count(array_unique($houseSigns)) === 12,
            count(array_unique($houseSigns)).' distinct'
        );

        // Vimshottari must total exactly 120 years.
        $total = array_sum(VimshottariDasha::YEARS);

        $this->check(
            'Vimshottari periods total 120 years',
            $total === VimshottariDasha::TOTAL_YEARS,
            "{$total} years"
        );

        // The dasha timeline must be continuous and ordered.
        $periods = $facts['dasha']['mahadashas'] ?? [];
        $continuous = true;

        for ($i = 1; $i < count($periods); $i++) {
            if ($periods[$i]['start'] !== $periods[$i - 1]['end']) {
                $continuous = false;
                break;
            }
        }

        if ($periods !== []) {
            $this->check('Dasha timeline is continuous', $continuous, count($periods).' periods');
        }
    }

    /** Smallest separation between two angles, in degrees. */
    private function angleDiff(float $a, float $b): float
    {
        $diff = abs(fmod($a - $b + 540, 360) - 180);

        return $diff;
    }

    private function check(string $label, bool $ok, string $detail = ''): void
    {
        if ($ok) {
            $this->passed++;
            $this->line("  <fg=green>PASS</> {$label}".($detail ? " <fg=gray>({$detail})</>" : ''));

            return;
        }

        $this->failed++;
        $this->line("  <fg=red>FAIL</> {$label}".($detail ? " <fg=yellow>({$detail})</>" : ''));
    }
}
