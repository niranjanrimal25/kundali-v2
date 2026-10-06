<?php

namespace App\Services\Astrology\Reading;

use App\Services\Astrology\Support\Zodiac;

/**
 * STEP 1 — Standardised chart data schema.
 *
 * Flattens the rich ChartFacts array into the small, predictable payload
 * the rule engine evaluates against. Rules address facts by dotted path:
 *
 *   planet.Sun.house          4
 *   planet.Sun.sign           7            (0 = Aries .. 11 = Pisces)
 *   planet.Sun.signNumber     8            (1-based, as written on a chart)
 *   planet.Sun.lordOf         [1]          bhavas this graha rules
 *   planet.Sun.dignity        "friendly"
 *   planet.Sun.combust        true|false
 *   planet.Sun.retrograde     true|false
 *   planet.Sun.conjunctWith   ["Jupiter"]  sharing the bhava
 *   planet.Sun.aspectedBy     ["Mars"]     receiving drishti
 *   planet.Sun.influencedBy   ["Jupiter","Mars"]   conjunct OR aspecting
 *   planet.Sun.inTrik         true|false   6th, 8th or 12th
 *   planet.Sun.inEnemySign    true|false   enemy or debilitated
 *   planet.Sun.withMalefic    true|false   shares a bhava with a malefic
 *   planet.Sun.aspectedByMalefic   true|false
 *   planet.Sun.afflicted      true|false   any of the three above
 *   planet.Sun.inTrikSign     true|false   occupies the rashi of a 4/8/12
 *                                          bhava, the unfavourable signs
 *   lagna.sign / lagna.signNumber
 *   house.4.sign / house.4.lord / house.4.occupants
 *
 * Keeping this layer thin and explicit means a rule author never has to
 * know how the ephemeris works, and the engine never has to know Vedic
 * astrology.
 */
class ChartPayload
{
    public const GRAHAS = ['Sun', 'Moon', 'Mars', 'Mercury', 'Jupiter', 'Venus', 'Saturn', 'Rahu', 'Ketu'];

    public static function fromFacts(array $facts): array
    {
        $lagnaSign = $facts['lagna']['sign'];

        $payload = [
            'lagna' => [
                'sign' => $lagnaSign,
                'signNumber' => $lagnaSign + 1,
                'signName' => $facts['lagna']['sign_name'],
                'signSanskrit' => $facts['lagna']['sign_sanskrit'],
                'lord' => Zodiac::SIGN_LORDS[$lagnaSign],
            ],
            'planet' => [],
            'house' => [],

            // Detector output passed through untouched. The rule engine
            // does not evaluate against these; the timeline analyser
            // reads them directly.
            'dasha' => $facts['dasha'] ?? [],
            'yogas' => $facts['yogas'] ?? [],
            'doshas' => $facts['doshas'] ?? [],
            'transits' => $facts['transits'] ?? [],
        ];

        // A planet in a Trik bhava is only read as harmful when it is
        // ALSO afflicted. Per the owner: Mars in the 6th does not mean
        // injury by itself; it must be joined by a malefic.
        $malefics = ['Saturn', 'Mars', 'Rahu', 'Ketu', 'Sun'];

        // --- grahas -------------------------------------------------
        foreach (self::GRAHAS as $name) {
            $p = $facts['planets'][$name] ?? null;

            if (! $p) {
                continue;
            }

            $conjunct = [];
            $aspectedBy = [];

            foreach (self::GRAHAS as $other) {
                if ($other === $name) {
                    continue;
                }

                $o = $facts['planets'][$other] ?? null;

                if (! $o) {
                    continue;
                }

                if ($o['house'] === $p['house']) {
                    $conjunct[] = $other;
                }

                if (in_array($p['house'], $o['aspects_houses'] ?? [], true)) {
                    $aspectedBy[] = $other;
                }
            }

            $withMalefic = array_values(array_intersect($conjunct, array_diff($malefics, [$name])));
            $aspectedByMalefic = array_values(array_intersect($aspectedBy, array_diff($malefics, [$name])));
            $inEnemySign = in_array($p['dignity'], ['enemy', 'debilitated'], true);

            // Signs belonging to the 4th, 8th and 12th bhavas - the
            // unfavourable signs referred to as the north direction.
            $trikSigns = [];
            foreach ([4, 8, 12] as $h) {
                $trikSigns[] = ($lagnaSign + $h - 1) % 12;
            }

            $payload['planet'][$name] = [
                'name' => $name,
                'sanskrit' => $p['sanskrit'],
                'house' => $p['house'],
                'sign' => $p['sign'],
                'signNumber' => $p['sign'] + 1,
                'signName' => $p['sign_name'],
                'signSanskrit' => $p['sign_sanskrit'],
                'degree' => $p['degree_in_sign'],
                'lordOf' => $p['owns_houses'] ?? [],
                'dignity' => $p['dignity'],
                'combust' => (bool) $p['combust'],
                'retrograde' => (bool) $p['retrograde'],
                'conjunctWith' => $conjunct,
                'aspectedBy' => $aspectedBy,
                // "With" in the source manual means influence generally.
                'influencedBy' => array_values(array_unique(array_merge($conjunct, $aspectedBy))),
                'inTrik' => in_array($p['house'], [6, 8, 12], true),
                'inEnemySign' => $inEnemySign,
                'withMalefic' => $withMalefic !== [],
                'aspectedByMalefic' => $aspectedByMalefic !== [],
                'maleficCompany' => $withMalefic,
                'afflicted' => $inEnemySign || $withMalefic !== [] || $aspectedByMalefic !== [],
                'inTrikSign' => in_array($p['sign'], $trikSigns, true),
                'inKendra' => in_array($p['house'], [1, 4, 7, 10], true),
                'inTrikona' => in_array($p['house'], [1, 5, 9], true),
                'aspectsHouses' => $p['aspects_houses'] ?? [],
                'digbala' => $p['digbala']['applicable'] ?? false
                    ? self::digbalaBand($p['digbala']['strength'])
                    : null,
            ];
        }

        // --- bhavas -------------------------------------------------
        for ($house = 1; $house <= 12; $house++) {
            $sign = ($lagnaSign + $house - 1) % 12;

            $occupants = [];
            foreach ($payload['planet'] as $name => $p) {
                if ($p['house'] === $house) {
                    $occupants[] = $name;
                }
            }

            $payload['house'][$house] = [
                'number' => $house,
                'sign' => $sign,
                'signNumber' => $sign + 1,
                'signName' => Zodiac::SIGNS[$sign],
                'signSanskrit' => Zodiac::SIGNS_SANSKRIT[$sign],
                'lord' => Zodiac::SIGN_LORDS[$sign],
                'occupants' => $occupants,
                'isTrik' => in_array($house, [6, 8, 12], true),
                'significations' => Zodiac::HOUSES[$house]['significations'],
                'label' => Zodiac::HOUSES[$house]['english'],
                'sanskritName' => Zodiac::HOUSES[$house]['name'],
            ];
        }

        // --- lord.N.* : the graha ruling bhava N, addressed by bhava ---
        // Lets a rule say "the lord of the 4th sits in the 8th" without
        // knowing which graha that is for this ascendant.
        for ($house = 1; $house <= 12; $house++) {
            $lord = $payload['house'][$house]['lord'];

            if (isset($payload['planet'][$lord])) {
                $payload['lord'][$house] = $payload['planet'][$lord];
            }
        }

        // --- sign.N.house : which bhava a given rashi falls on ---
        for ($sign = 0; $sign < 12; $sign++) {
            $payload['sign'][$sign] = [
                'house' => ((($sign - $lagnaSign + 12) % 12) + 1),
                'number' => $sign + 1,
                'name' => Zodiac::SIGNS[$sign],
            ];
        }

        return $payload;
    }

    private static function digbalaBand(?float $strength): ?string
    {
        if ($strength === null) {
            return null;
        }

        return match (true) {
            $strength >= 0.84 => 'full',
            $strength >= 0.6 => 'strong',
            $strength > 0.16 => 'weak',
            default => 'powerless',
        };
    }

    /**
     * Resolve a dotted fact path against the payload.
     * Returns null when the path does not exist, which the engine
     * treats as "cannot be satisfied" rather than as a match.
     */
    public static function get(array $payload, string $path): mixed
    {
        $value = $payload;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
