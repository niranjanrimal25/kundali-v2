<?php

namespace App\Services\Astrology\Interpretation;

use App\Services\Astrology\Support\Zodiac;

/**
 * Evaluates the composite conditions stored in interpretation_rules.conditions.
 *
 * Until now every rule was a single lookup ("Saturn in the 7th"). Real
 * classical rules are compound: "if the Lagna lord is in the 6th, 8th or
 * 12th AND conjunct Rahu or Ketu, then ...". This evaluates that shape.
 *
 * The condition JSON is a flat object; EVERY key present must match.
 *
 *   {
 *     "planet":      "Sun",                  the graha the rule is about
 *     "lord_of":     [1,4,6,8],              it must rule one of these bhavas
 *     "in_house":    [6,8,7,4,12],           and sit in one of these
 *     "in_sign":     [0,4,7,8],              and occupy one of these rashis
 *     "with_any":    ["Rahu","Ketu"],        sharing a bhava with ANY of these
 *     "with_all":    ["Moon","Venus"],       sharing a bhava with ALL of these
 *     "aspected_by": ["Saturn"],             receiving drishti from ANY of these
 *     "lagna_sign":  0,                      only for this ascendant
 *     "sign_in_house": {"sign":3,"house":[6,8,2,10]}   a rashi falling on a bhava
 *   }
 *
 * Unknown keys are ignored rather than silently failing the match, so a
 * typo in a seeded rule surfaces as "rule never fires" in the audit
 * command rather than as a wrong reading.
 */
class ConditionMatcher
{
    /** Keys this evaluator understands. Anything else is reported by audit(). */
    public const SUPPORTED = [
        'planet', 'lord_of', 'in_house', 'in_sign', 'with_any', 'with_all',
        'aspected_by', 'lagna_sign', 'sign_in_house', 'not_in_house',
        'companion_lord_of', 'degree_below', 'lord_in_house',
        'influenced_by',
    ];

    public function matches(array $conditions, array $facts): bool
    {
        if ($conditions === []) {
            return false;
        }

        $lagnaSign = $facts['lagna']['sign'];

        // Which graha is this rule about?
        $name = $conditions['planet'] ?? null;
        $planet = $name ? ($facts['planets'][$name] ?? null) : null;

        if ($name !== null && $planet === null) {
            return false;
        }

        if (isset($conditions['lagna_sign']) && $lagnaSign !== (int) $conditions['lagna_sign']) {
            return false;
        }

        if (isset($conditions['sign_in_house'])) {
            $spec = $conditions['sign_in_house'];
            $house = $this->houseOfSign((int) $spec['sign'], $lagnaSign);

            if (! in_array($house, (array) $spec['house'], true)) {
                return false;
            }
        }

        // A rule may name its subject implicitly, as "the lord of the Nth".
        // Resolve that to an actual graha, otherwise every placement test
        // below has nothing to test and the rule matches any chart at all.
        if ($planet === null && isset($conditions['lord_of'])) {
            $planet = $this->rulerOf((array) $conditions['lord_of'], $lagnaSign, $facts);

            if ($planet === null) {
                return false;
            }

            // Lordship is now satisfied by construction.
            unset($conditions['lord_of']);
        }

        if ($planet === null) {
            // Genuinely chart-level: everything testable is already checked.
            return true;
        }

        // A named OTHER graha must rule one set of houses and sit in another.
        if (isset($conditions['lord_in_house'])) {
            foreach ($conditions['lord_in_house'] as $spec) {
                $ruler = null;

                foreach (Zodiac::OWN_SIGNS as $graha => $signs) {
                    if (array_intersect($this->housesRuledBy($graha, $lagnaSign), (array) $spec['lord_of'])) {
                        $ruler = $facts['planets'][$graha] ?? null;
                        break;
                    }
                }

                if (! $ruler || ! in_array($ruler['house'], (array) $spec['in_house'], true)) {
                    return false;
                }
            }
        }

        if (isset($conditions['lord_of'])) {
            $owned = $this->housesRuledBy($name, $lagnaSign);

            if (array_intersect($owned, (array) $conditions['lord_of']) === []) {
                return false;
            }
        }

        if (isset($conditions['in_house'])
            && ! in_array($planet['house'], (array) $conditions['in_house'], true)) {
            return false;
        }

        if (isset($conditions['not_in_house'])
            && in_array($planet['house'], (array) $conditions['not_in_house'], true)) {
            return false;
        }

        if (isset($conditions['in_sign'])
            && ! in_array($planet['sign'], (array) $conditions['in_sign'], true)) {
            return false;
        }

        if (isset($conditions['with_any']) && ! $this->conjunctAny($planet, (array) $conditions['with_any'], $facts)) {
            return false;
        }

        if (isset($conditions['with_all']) && ! $this->conjunctAll($planet, (array) $conditions['with_all'], $facts)) {
            return false;
        }

        if (isset($conditions['aspected_by']) && ! $this->aspectedByAny($planet, (array) $conditions['aspected_by'], $facts)) {
            return false;
        }

        // The source says "with" to mean influence generally, which in
        // practice covers a graha sharing the bhava OR casting drishti
        // onto it. Treating it as conjunction only made rules miss the
        // cases the owner's own worked example relies on.
        if (isset($conditions['influenced_by'])) {
            $others = (array) $conditions['influenced_by'];

            if (! $this->conjunctAny($planet, $others, $facts)
                && ! $this->aspectedByAny($planet, $others, $facts)) {
                return false;
            }
        }

        // Companions sharing the bhava must themselves rule given houses.
        if (isset($conditions['companion_lord_of'])) {
            $spec = $conditions['companion_lord_of'];

            foreach ((array) $spec['planets'] as $companion) {
                $owned = $this->housesRuledBy($companion, $lagnaSign);

                if (array_intersect($owned, (array) $spec['houses']) === []) {
                    return false;
                }
            }
        }

        // Degree within its own sign, used for "low degree" conditions.
        if (isset($conditions['degree_below'])
            && fmod($planet['longitude'], 30) >= (float) $conditions['degree_below']) {
            return false;
        }

        return true;
    }

    /** The graha ruling any of the given bhavas, if one is placed. */
    private function rulerOf(array $houses, int $lagnaSign, array $facts): ?array
    {
        foreach ($houses as $house) {
            $sign = ($lagnaSign + $house - 1) % 12;
            $lord = Zodiac::SIGN_LORDS[$sign] ?? null;

            if ($lord && isset($facts['planets'][$lord])) {
                return $facts['planets'][$lord];
            }
        }

        return null;
    }

    /** Bhavas ruled by a graha, relative to the Lagna. */
    public function housesRuledBy(string $planet, int $lagnaSign): array
    {
        $houses = [];

        foreach (Zodiac::OWN_SIGNS[$planet] ?? [] as $sign) {
            $houses[] = $this->houseOfSign($sign, $lagnaSign);
        }

        sort($houses);

        return $houses;
    }

    private function houseOfSign(int $sign, int $lagnaSign): int
    {
        return (($sign - $lagnaSign + 12) % 12) + 1;
    }

    private function conjunctAny(array $planet, array $others, array $facts): bool
    {
        foreach ($others as $other) {
            $o = $facts['planets'][$other] ?? null;

            if ($o && $o['name'] !== $planet['name'] && $o['house'] === $planet['house']) {
                return true;
            }
        }

        return false;
    }

    private function conjunctAll(array $planet, array $others, array $facts): bool
    {
        foreach ($others as $other) {
            $o = $facts['planets'][$other] ?? null;

            if (! $o || $o['house'] !== $planet['house']) {
                return false;
            }
        }

        return true;
    }

    private function aspectedByAny(array $planet, array $others, array $facts): bool
    {
        foreach ($others as $other) {
            $o = $facts['planets'][$other] ?? null;

            if (! $o || $o['name'] === $planet['name']) {
                continue;
            }

            if (in_array($planet['house'], $o['aspects_houses'] ?? [], true)) {
                return true;
            }
        }

        return false;
    }

    /** Keys used by a rule that this evaluator does not understand. */
    public function unknownKeys(array $conditions): array
    {
        return array_values(array_diff(array_keys($conditions), self::SUPPORTED));
    }
}
