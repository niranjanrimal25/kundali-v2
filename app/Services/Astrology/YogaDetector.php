<?php

namespace App\Services\Astrology;

use App\Services\Astrology\Support\Zodiac;

/**
 * Detects the standard Parashari yogas present in a chart.
 *
 * Each detection returns a stable `key` that the interpretation corpus
 * keys against, plus the factual basis for the claim so a reader can
 * check the reasoning rather than take it on trust.
 *
 * Only yogas with unambiguous, testable definitions are implemented.
 * Where classical sources disagree on a definition, the majority
 * convention is used and the choice is documented in the method.
 */
class YogaDetector
{
    /**
     * @param  array  $planets  keyed by graha name, from ChartCalculator
     * @return list<array{key:string,name:string,basis:string,strength:string}>
     */
    public function detect(array $planets, int $lagnaSign): array
    {
        $yogas = [];

        foreach (
            [
                $this->panchaMahapurusha($planets),
                $this->gajaKesari($planets),
                $this->budhaAditya($planets),
                $this->chandraMangala($planets),
                $this->kemadruma($planets),
                $this->vipareetaRaja($planets, $lagnaSign),
                $this->rajaYoga($planets, $lagnaSign),
                $this->dhanaYoga($planets, $lagnaSign),
            ] as $group
        ) {
            foreach ($group as $yoga) {
                $yogas[] = $yoga;
            }
        }

        return $yogas;
    }

    /**
     * Pancha Mahapurusha — one of the five "great person" yogas.
     * Formed when Mars, Mercury, Jupiter, Venus or Saturn occupies its
     * own sign or exaltation sign AND sits in a kendra from the Lagna.
     */
    private function panchaMahapurusha(array $planets): array
    {
        $names = [
            'Mars' => 'Ruchaka',
            'Mercury' => 'Bhadra',
            'Jupiter' => 'Hamsa',
            'Venus' => 'Malavya',
            'Saturn' => 'Sasa',
        ];

        $found = [];

        foreach ($names as $graha => $yogaName) {
            $planet = $planets[$graha] ?? null;

            if ($planet === null) {
                continue;
            }

            $strongDignity = in_array($planet['dignity'], ['exalted', 'own', 'moolatrikona'], true);
            $inKendra = in_array($planet['house'], Zodiac::KENDRA, true);

            if ($strongDignity && $inKendra) {
                $found[] = [
                    'key' => 'panchamahapurusha_'.strtolower($yogaName),
                    'name' => $yogaName.' Yoga',
                    'basis' => sprintf(
                        '%s is %s in %s and occupies the %s house, a kendra.',
                        $planet['sanskrit'],
                        $planet['dignity'],
                        $planet['sign_name'],
                        $this->ordinal($planet['house'])
                    ),
                    'strength' => $planet['dignity'] === 'exalted' ? 'strong' : 'moderate',
                ];
            }
        }

        return $found;
    }

    /**
     * Gaja Kesari — Jupiter in a kendra (1/4/7/10) counted FROM the Moon,
     * not from the Lagna. This is the common convention.
     */
    private function gajaKesari(array $planets): array
    {
        $moon = $planets['Moon'] ?? null;
        $jupiter = $planets['Jupiter'] ?? null;

        if (! $moon || ! $jupiter) {
            return [];
        }

        $fromMoon = $this->houseFrom($moon['sign'], $jupiter['sign']);

        if (! in_array($fromMoon, Zodiac::KENDRA, true)) {
            return [];
        }

        return [[
            'key' => 'gaja_kesari',
            'name' => 'Gaja Kesari Yoga',
            'basis' => sprintf(
                'Guru stands in the %s house from Chandra, a kendra from the Moon.',
                $this->ordinal($fromMoon)
            ),
            'strength' => in_array($jupiter['dignity'], ['exalted', 'own', 'moolatrikona'], true)
                ? 'strong'
                : 'moderate',
        ]];
    }

    /** Budha-Aditya — Sun and Mercury in the same bhava. */
    private function budhaAditya(array $planets): array
    {
        $sun = $planets['Sun'] ?? null;
        $mercury = $planets['Mercury'] ?? null;

        if (! $sun || ! $mercury || $sun['house'] !== $mercury['house']) {
            return [];
        }

        return [[
            'key' => 'budha_aditya',
            'name' => 'Budha-Aditya Yoga',
            'basis' => sprintf(
                'Surya and Budha share the %s bhava.',
                $this->ordinal($sun['house'])
            ),
            // Combustion is the standard caveat on this yoga.
            'strength' => $mercury['combust'] ? 'qualified' : 'moderate',
        ]];
    }

    /** Chandra-Mangala — Moon and Mars in the same bhava. */
    private function chandraMangala(array $planets): array
    {
        $moon = $planets['Moon'] ?? null;
        $mars = $planets['Mars'] ?? null;

        if (! $moon || ! $mars || $moon['house'] !== $mars['house']) {
            return [];
        }

        return [[
            'key' => 'chandra_mangala',
            'name' => 'Chandra-Mangala Yoga',
            'basis' => sprintf(
                'Chandra and Mangala share the %s bhava.',
                $this->ordinal($moon['house'])
            ),
            'strength' => 'moderate',
        ]];
    }

    /**
     * Kemadruma — no graha in the 2nd or 12th from the Moon, and no graha
     * with the Moon. The Sun and the nodes are excluded from the count,
     * which is the majority convention.
     */
    private function kemadruma(array $planets): array
    {
        $moon = $planets['Moon'] ?? null;

        if (! $moon) {
            return [];
        }

        $excluded = ['Moon', 'Sun', 'Rahu', 'Ketu'];

        foreach ($planets as $name => $planet) {
            if (in_array($name, $excluded, true)) {
                continue;
            }

            $from = $this->houseFrom($moon['sign'], $planet['sign']);

            if (in_array($from, [1, 2, 12], true)) {
                return []; // cancelled
            }
        }

        return [[
            'key' => 'kemadruma',
            'name' => 'Kemadruma Yoga',
            'basis' => 'No graha occupies the 2nd or 12th from Chandra, and none accompanies it.',
            'strength' => 'moderate',
        ]];
    }

    /**
     * Vipareeta Raja Yoga — a lord of the 6th, 8th or 12th placed in
     * another of those same three houses.
     */
    private function vipareetaRaja(array $planets, int $lagnaSign): array
    {
        $dusthanas = [6, 8, 12];
        $found = [];

        foreach ($dusthanas as $house) {
            $lord = $this->lordOfHouse($house, $lagnaSign);
            $planet = $planets[$lord] ?? null;

            if (! $planet || ! in_array($planet['house'], $dusthanas, true)) {
                continue;
            }

            $found[] = [
                'key' => 'vipareeta_raja',
                'name' => 'Vipareeta Raja Yoga',
                'basis' => sprintf(
                    'The lord of the %s bhava, %s, sits in the %s bhava — one dusthana lord in another.',
                    $this->ordinal($house),
                    $planet['sanskrit'],
                    $this->ordinal($planet['house'])
                ),
                'strength' => 'moderate',
            ];
        }

        // One mention is enough however many times the pattern repeats.
        return array_slice($found, 0, 1);
    }

    /**
     * Raja Yoga — a kendra lord and a trikona lord conjoined in one bhava.
     * Association by aspect or exchange is also classical, but conjunction
     * is the unambiguous case, so only that is claimed here.
     */
    private function rajaYoga(array $planets, int $lagnaSign): array
    {
        $kendraLords = [];
        $trikonaLords = [];

        foreach (Zodiac::KENDRA as $house) {
            $kendraLords[$this->lordOfHouse($house, $lagnaSign)][] = $house;
        }

        foreach (Zodiac::TRIKONA as $house) {
            $trikonaLords[$this->lordOfHouse($house, $lagnaSign)][] = $house;
        }

        foreach ($kendraLords as $kLord => $kHouses) {
            foreach ($trikonaLords as $tLord => $tHouses) {
                if ($kLord === $tLord) {
                    continue; // same graha owning both is a different rule
                }

                $a = $planets[$kLord] ?? null;
                $b = $planets[$tLord] ?? null;

                if (! $a || ! $b || $a['house'] !== $b['house']) {
                    continue;
                }

                return [[
                    'key' => 'raja_yoga',
                    'name' => 'Raja Yoga',
                    'basis' => sprintf(
                        '%s, lord of the %s, joins %s, lord of the %s, in the %s bhava.',
                        $a['sanskrit'],
                        $this->ordinal($kHouses[0]),
                        $b['sanskrit'],
                        $this->ordinal($tHouses[0]),
                        $this->ordinal($a['house'])
                    ),
                    'strength' => 'strong',
                ]];
            }
        }

        return [];
    }

    /** Dhana Yoga — lords of the 2nd and 11th conjoined. */
    private function dhanaYoga(array $planets, int $lagnaSign): array
    {
        $second = $this->lordOfHouse(2, $lagnaSign);
        $eleventh = $this->lordOfHouse(11, $lagnaSign);

        if ($second === $eleventh) {
            return [];
        }

        $a = $planets[$second] ?? null;
        $b = $planets[$eleventh] ?? null;

        if (! $a || ! $b || $a['house'] !== $b['house']) {
            return [];
        }

        return [[
            'key' => 'dhana_yoga',
            'name' => 'Dhana Yoga',
            'basis' => sprintf(
                'The lords of the 2nd and 11th, %s and %s, meet in the %s bhava.',
                $a['sanskrit'],
                $b['sanskrit'],
                $this->ordinal($a['house'])
            ),
            'strength' => 'moderate',
        ]];
    }

    /** Which graha rules the sign on a given house. */
    private function lordOfHouse(int $house, int $lagnaSign): string
    {
        $sign = ($lagnaSign + $house - 1) % 12;

        return Zodiac::SIGN_LORDS[$sign];
    }

    /** House number of $targetSign counted from $fromSign, 1-based. */
    private function houseFrom(int $fromSign, int $targetSign): int
    {
        return (($targetSign - $fromSign + 12) % 12) + 1;
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
