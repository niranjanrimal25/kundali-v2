<?php

namespace App\Services\Astrology;

use App\Services\Astrology\Support\Nakshatras;
use DateTimeImmutable;

/**
 * Vimshottari Dasha — the 120-year planetary period system that gives
 * Vedic astrology its predictive timing.
 *
 * The sequence and starting point are determined entirely by the Moon's
 * position: the nakshatra lord at birth owns the first Mahadasha, and the
 * portion of that nakshatra already traversed is the portion of the
 * Mahadasha already elapsed.
 */
class VimshottariDasha
{
    /** Fixed order of the cycle. */
    public const ORDER = ['Ketu', 'Venus', 'Sun', 'Moon', 'Mars', 'Rahu', 'Jupiter', 'Saturn', 'Mercury'];

    /** Mahadasha length in years. Totals 120. */
    public const YEARS = [
        'Ketu' => 7, 'Venus' => 20, 'Sun' => 6, 'Moon' => 10, 'Mars' => 7,
        'Rahu' => 18, 'Jupiter' => 16, 'Saturn' => 19, 'Mercury' => 17,
    ];

    public const TOTAL_YEARS = 120;

    /** Days in a nominal astrological year (365.25). */
    private const DAYS_PER_YEAR = 365.25;

    /**
     * Build the dasha tree from birth, flagging whichever periods are
     * running as of $asOf (defaults to now).
     */
    public function build(float $moonLongitude, DateTimeImmutable $birthUtc, ?DateTimeImmutable $asOf = null): array
    {
        $asOf ??= new DateTimeImmutable('now');

        $nakshatraIndex = Nakshatras::index($moonLongitude);
        $startLord = Nakshatras::LORDS[$nakshatraIndex];

        // Fraction of the birth nakshatra already crossed = fraction of
        // the first Mahadasha already consumed.
        $traversed = Nakshatras::fractionTraversed($moonLongitude);
        $balanceFraction = 1.0 - $traversed;

        $firstDashaYears = self::YEARS[$startLord];
        $balanceYears = $firstDashaYears * $balanceFraction;

        // Wind back to the notional start of the first Mahadasha so the
        // whole sequence can be generated forward consistently.
        $elapsedDays = ($firstDashaYears - $balanceYears) * self::DAYS_PER_YEAR;
        $cursor = $this->addDays($birthUtc, -$elapsedDays);

        $startIndex = array_search($startLord, self::ORDER, true);

        $mahadashas = [];

        // Two full cycles covers 240 years — beyond any lifespan.
        for ($i = 0; $i < 18; $i++) {
            $lord = self::ORDER[($startIndex + $i) % 9];
            $years = self::YEARS[$lord];

            $start = $cursor;
            $end = $this->addDays($cursor, $years * self::DAYS_PER_YEAR);

            $isCurrent = $asOf >= $start && $asOf < $end;

            $mahadashas[] = [
                'lord' => $lord,
                'years' => $years,
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'current' => $isCurrent,
                'antardashas' => $this->antardashas($lord, $start, $years, $asOf),
            ];

            $cursor = $end;

            if ($cursor > $this->addDays($birthUtc, 120 * self::DAYS_PER_YEAR)) {
                break;
            }
        }

        $current = $this->findCurrent($mahadashas);

        return [
            'birth_nakshatra' => Nakshatras::NAMES[$nakshatraIndex],
            'birth_nakshatra_lord' => $startLord,
            'balance_at_birth' => [
                'lord' => $startLord,
                'years' => (int) floor($balanceYears),
                'months' => (int) floor(fmod($balanceYears, 1) * 12),
                'days' => (int) round(fmod(fmod($balanceYears, 1) * 12, 1) * 30),
                'decimal_years' => round($balanceYears, 4),
            ],
            'mahadashas' => $mahadashas,
            'current' => $current,
        ];
    }

    /**
     * Antardashas (bhuktis) within a Mahadasha. Each sub-period is
     * proportional: (antardasha years x mahadasha years) / 120.
     */
    private function antardashas(string $mahaLord, DateTimeImmutable $mahaStart, int $mahaYears, DateTimeImmutable $asOf): array
    {
        $startIndex = array_search($mahaLord, self::ORDER, true);
        $cursor = $mahaStart;
        $periods = [];

        for ($i = 0; $i < 9; $i++) {
            $lord = self::ORDER[($startIndex + $i) % 9];

            $years = ($mahaYears * self::YEARS[$lord]) / self::TOTAL_YEARS;
            $end = $this->addDays($cursor, $years * self::DAYS_PER_YEAR);

            $isCurrent = $asOf >= $cursor && $asOf < $end;

            $periods[] = [
                'lord' => $lord,
                'start' => $cursor->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'current' => $isCurrent,
                'pratyantardashas' => $isCurrent
                    ? $this->pratyantardashas($mahaLord, $lord, $cursor, $years, $asOf)
                    : [],
            ];

            $cursor = $end;
        }

        return $periods;
    }

    /** Third-level sub-periods, only expanded for the running Antardasha. */
    private function pratyantardashas(string $mahaLord, string $antarLord, DateTimeImmutable $start, float $antarYears, DateTimeImmutable $asOf): array
    {
        $startIndex = array_search($antarLord, self::ORDER, true);
        $cursor = $start;
        $periods = [];

        for ($i = 0; $i < 9; $i++) {
            $lord = self::ORDER[($startIndex + $i) % 9];

            $years = ($antarYears * self::YEARS[$lord]) / self::TOTAL_YEARS;
            $end = $this->addDays($cursor, $years * self::DAYS_PER_YEAR);

            $periods[] = [
                'lord' => $lord,
                'start' => $cursor->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'current' => $asOf >= $cursor && $asOf < $end,
            ];

            $cursor = $end;
        }

        return $periods;
    }

    /** Extract the running Maha / Antar / Pratyantar triple. */
    private function findCurrent(array $mahadashas): array
    {
        foreach ($mahadashas as $maha) {
            if (! $maha['current']) {
                continue;
            }

            $currentAntar = null;
            $currentPratyantar = null;

            foreach ($maha['antardashas'] as $antar) {
                if ($antar['current']) {
                    $currentAntar = $antar;
                    foreach ($antar['pratyantardashas'] as $prat) {
                        if ($prat['current']) {
                            $currentPratyantar = $prat;
                        }
                    }
                }
            }

            return [
                'mahadasha' => ['lord' => $maha['lord'], 'start' => $maha['start'], 'end' => $maha['end']],
                'antardasha' => $currentAntar ? ['lord' => $currentAntar['lord'], 'start' => $currentAntar['start'], 'end' => $currentAntar['end']] : null,
                'pratyantardasha' => $currentPratyantar ? ['lord' => $currentPratyantar['lord'], 'start' => $currentPratyantar['start'], 'end' => $currentPratyantar['end']] : null,
            ];
        }

        return ['mahadasha' => null, 'antardasha' => null, 'pratyantardasha' => null];
    }

    /** Add a fractional number of days, preserving sub-day precision. */
    private function addDays(DateTimeImmutable $date, float $days): DateTimeImmutable
    {
        $seconds = (int) round($days * 86400);

        return $date->modify(($seconds >= 0 ? '+' : '').$seconds.' seconds');
    }
}
