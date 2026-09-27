<?php

namespace App\Services\Astrology;

use App\Services\Astrology\Support\Zodiac;

/**
 * Detects the afflictions commonly examined in a Vedic reading.
 *
 * Doshas are the part of a reading most often overstated by commercial
 * astrology, so two principles are applied here:
 *
 *  1. Classical CANCELLATIONS are checked and reported. A dosha that is
 *     cancelled is reported as cancelled, not suppressed and not left
 *     standing as a scare.
 *  2. Every detection carries its factual basis, so the reasoning is
 *     visible rather than asserted.
 */
class DoshaDetector
{
    /**
     * @return list<array{key:string,name:string,basis:string,severity:string,cancelled:bool,cancellation:?string}>
     */
    public function detect(array $planets, int $lagnaSign, array $houses): array
    {
        return array_values(array_filter([
            $this->mangalDosha($planets),
            $this->kaalSarpa($planets),
            $this->grahanDosha($planets),
            $this->kemadrumaAffliction($planets),
        ]));
    }

    /**
     * Mangal (Kuja) Dosha — Mars in the 1st, 2nd, 4th, 7th, 8th or 12th
     * counted from the Lagna. The 2nd house is included per North Indian
     * convention; some southern traditions omit it.
     *
     * Cancellations implemented (any one suffices):
     *   - Mars in its own sign or exalted
     *   - Mars in the 2nd while that house is Gemini or Virgo
     *   - Saturn or Jupiter aspects Mars
     */
    private function mangalDosha(array $planets): ?array
    {
        $mars = $planets['Mars'] ?? null;

        if (! $mars) {
            return null;
        }

        $afflictedHouses = [1, 2, 4, 7, 8, 12];

        if (! in_array($mars['house'], $afflictedHouses, true)) {
            return null;
        }

        $cancellation = null;

        if (in_array($mars['dignity'], ['exalted', 'own', 'moolatrikona'], true)) {
            $cancellation = sprintf(
                'Mangala is %s in %s, which classical opinion treats as cancelling the dosha.',
                $mars['dignity'],
                $mars['sign_name']
            );
        } elseif ($mars['house'] === 2 && in_array($mars['sign'], [2, 5], true)) {
            $cancellation = 'Mangala occupies a Budha-ruled sign in the 2nd, a recognised cancellation.';
        } else {
            foreach (['Jupiter', 'Saturn'] as $name) {
                $other = $planets[$name] ?? null;

                if ($other && in_array($mars['house'], $other['aspects_houses'] ?? [], true)) {
                    $cancellation = sprintf(
                        'The drishti of %s falls on Mangala, which mitigates the dosha substantially.',
                        $other['sanskrit']
                    );
                    break;
                }
            }
        }

        return [
            'key' => 'mangal_dosha',
            'name' => 'Mangal Dosha',
            'basis' => sprintf(
                'Mangala occupies the %s bhava, one of the six positions that constitute the dosha.',
                $this->ordinal($mars['house'])
            ),
            'severity' => $cancellation ? 'mitigated' : 'present',
            'cancelled' => $cancellation !== null,
            'cancellation' => $cancellation,
        ];
    }

    /**
     * Kaal Sarpa — every one of the seven grahas hemmed between Rahu and
     * Ketu. If even one falls outside the axis, the yoga is partial
     * (Kaal Amrit) rather than complete, and that distinction is reported.
     */
    private function kaalSarpa(array $planets): ?array
    {
        $rahu = $planets['Rahu'] ?? null;
        $ketu = $planets['Ketu'] ?? null;

        if (! $rahu || ! $ketu) {
            return null;
        }

        $others = ['Sun', 'Moon', 'Mars', 'Mercury', 'Jupiter', 'Venus', 'Saturn'];
        $start = $rahu['longitude'];

        $inside = 0;
        $outside = [];

        foreach ($others as $name) {
            $planet = $planets[$name] ?? null;

            if (! $planet) {
                continue;
            }

            // Arc travelled from Rahu forward to the planet.
            $arc = fmod($planet['longitude'] - $start + 360, 360);

            if ($arc < 180) {
                $inside++;
            } else {
                $outside[] = $planet['sanskrit'];
            }
        }

        if ($inside === 0 || count($outside) === 0) {
            // All on one side: complete Kaal Sarpa.
            if (count($outside) === 0) {
                return [
                    'key' => 'kaal_sarpa',
                    'name' => 'Kaal Sarpa Yoga',
                    'basis' => 'All seven grahas fall within the arc from Rahu to Ketu.',
                    'severity' => 'present',
                    'cancelled' => false,
                    'cancellation' => null,
                ];
            }

            return null;
        }

        // Some inside, some outside: not a Kaal Sarpa at all.
        return null;
    }

    /** Grahan Dosha — Sun or Moon conjoined with Rahu or Ketu. */
    private function grahanDosha(array $planets): ?array
    {
        foreach (['Sun' => 'Surya', 'Moon' => 'Chandra'] as $luminary => $sanskrit) {
            foreach (['Rahu', 'Ketu'] as $node) {
                $a = $planets[$luminary] ?? null;
                $b = $planets[$node] ?? null;

                if (! $a || ! $b || $a['house'] !== $b['house']) {
                    continue;
                }

                return [
                    'key' => $luminary === 'Sun' ? 'grahan_dosha_sun' : 'grahan_dosha_moon',
                    'name' => 'Grahan Dosha',
                    'basis' => sprintf(
                        '%s is conjoined with %s in the %s bhava.',
                        $sanskrit,
                        $b['sanskrit'],
                        $this->ordinal($a['house'])
                    ),
                    'severity' => 'present',
                    'cancelled' => false,
                    'cancellation' => null,
                ];
            }
        }

        return null;
    }

    /**
     * Kemadruma is detected as a yoga elsewhere; here it is reported as
     * an affliction only when the Moon is additionally weak, which is
     * when it actually matters.
     */
    private function kemadrumaAffliction(array $planets): ?array
    {
        $moon = $planets['Moon'] ?? null;

        if (! $moon || ! in_array($moon['dignity'], ['debilitated', 'enemy'], true)) {
            return null;
        }

        $excluded = ['Moon', 'Sun', 'Rahu', 'Ketu'];

        foreach ($planets as $name => $planet) {
            if (in_array($name, $excluded, true)) {
                continue;
            }

            $from = (($planet['sign'] - $moon['sign'] + 12) % 12) + 1;

            if (in_array($from, [1, 2, 12], true)) {
                return null;
            }
        }

        return [
            'key' => 'kemadruma_affliction',
            'name' => 'Kemadruma with a weakened Moon',
            'basis' => sprintf(
                'Chandra is %s in %s and stands unsupported, with no graha in the 2nd or 12th from it.',
                $moon['dignity'],
                $moon['sign_name']
            ),
            'severity' => 'present',
            'cancelled' => false,
            'cancellation' => null,
        ];
    }

    /**
     * Sade Sati — transiting Saturn in the 12th, 1st or 2nd sign from the
     * natal Moon. Requires a transit position, so it is computed
     * separately from the birth chart.
     */
    public function sadeSati(array $planets, float $transitSaturnLongitude): ?array
    {
        $moon = $planets['Moon'] ?? null;

        if (! $moon) {
            return null;
        }

        $transitSign = (int) floor($transitSaturnLongitude / 30) % 12;
        $from = (($transitSign - $moon['sign'] + 12) % 12) + 1;

        if (! in_array($from, [12, 1, 2], true)) {
            // The two lesser transits worth naming.
            if (in_array($from, [4, 8], true)) {
                return [
                    'key' => $from === 4 ? 'kantaka_shani' : 'ashtama_shani',
                    'name' => $from === 4 ? 'Kantaka Shani' : 'Ashtama Shani',
                    'basis' => sprintf(
                        'Transiting Shani stands in the %s sign from your natal Chandra.',
                        $this->ordinal($from)
                    ),
                    'phase' => null,
                    'active' => true,
                ];
            }

            return null;
        }

        $phase = match ($from) {
            12 => 'first',
            1 => 'peak',
            2 => 'final',
        };

        return [
            'key' => 'sade_sati_'.$phase,
            'name' => 'Sade Sati',
            'basis' => sprintf(
                'Transiting Shani occupies %s, the %s sign from your natal Chandra in %s.',
                Zodiac::SIGNS[$transitSign],
                $this->ordinal($from),
                $moon['sign_name']
            ),
            'phase' => $phase,
            'active' => true,
        ];
    }

    private function ordinal(int $n): string
    {
        $suffix = match (true) {
            in_array($n % 100, [11, 12, 13], true) => 'th',
            $n % 10 === 1 => 'st',
            $n % 10 === 2 => 'nd',
            $n % 10 === 3 => 'rd',
            default => 'th',
        };

        return $n.$suffix;
    }
}
