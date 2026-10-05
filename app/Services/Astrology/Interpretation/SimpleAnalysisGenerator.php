<?php

namespace App\Services\Astrology\Interpretation;

use App\Services\Astrology\Support\Zodiac;

/**
 * The plain-language analysis view.
 *
 * A second presentation of the same rule corpus, laid out the way the
 * project owner asked for it:
 *
 *   1. Chart Placement Overview - occupied bhavas only
 *   2. Detailed Analysis        - grouped by graha, conjunctions merged
 *   3. Summary of Key Outcomes  - findings bucketed by life area
 *
 * It shares the engine with the long-form reading; only the shape of
 * the output differs. Nothing here re-interprets anything.
 */
class SimpleAnalysisGenerator
{
    private const ORDER = ['Sun', 'Moon', 'Mars', 'Mercury', 'Jupiter', 'Venus', 'Saturn', 'Rahu', 'Ketu'];

    public const CATEGORIES = [
        'health' => 'Health & Body',
        'mind' => 'Mind & Temperament',
        'relationships' => 'Relationships',
        'career' => 'Career & Finances',
    ];

    public function generate(array $facts, string $locale = 'en'): array
    {
        $rules = new RuleRepository($locale);
        $matcher = new ConditionMatcher;

        $fired = $this->firedRules($facts, $rules, $matcher);

        return [
            'placements' => $this->placements($facts),
            'analysis' => $this->analysis($facts, $rules, $fired),
            'summary' => $this->summary($fired),
        ];
    }

    /** Occupied bhavas only; empty houses are deliberately omitted. */
    private function placements(array $facts): array
    {
        $byHouse = [];

        foreach (self::ORDER as $name) {
            $planet = $facts['planets'][$name] ?? null;

            if ($planet) {
                $byHouse[$planet['house']][] = $planet;
            }
        }

        ksort($byHouse);

        $out = [];

        foreach ($byHouse as $house => $planets) {
            $first = $planets[0];

            $out[] = [
                'house' => $house,
                'ordinal' => $this->ordinal($house),
                'is_lagna' => $house === 1,
                'sign_name' => $first['sign_name'],
                'sign_sanskrit' => $first['sign_sanskrit'],
                'sign_number' => $first['sign'] + 1,
                'grahas' => array_map(fn ($p) => [
                    'name' => $p['name'],
                    'sanskrit' => $p['sanskrit'],
                    'glyph' => mb_substr($p['name'], 0, 2),
                    'combust' => (bool) $p['combust'],
                    'retrograde' => (bool) $p['retrograde'],
                ], $planets),
            ];
        }

        return $out;
    }

    /**
     * Every rule that matches this chart, tagged with the graha it is
     * about so the analysis can be grouped by planet.
     *
     * @return list<array{planet:?string, house:?int, text:string, category:string}>
     */
    private function firedRules(array $facts, RuleRepository $rules, ConditionMatcher $matcher): array
    {
        $fired = [];

        // Compound rules.
        foreach ($rules->all('composite') as $rule) {
            $conditions = json_decode($rule->conditions ?? '[]', true) ?: [];

            if (! $matcher->matches($conditions, $facts)) {
                continue;
            }

            $subject = $conditions['planet'] ?? $this->lordSubject($conditions, $facts);

            $fired[] = [
                'planet' => $subject,
                'house' => $subject ? ($facts['planets'][$subject]['house'] ?? null) : null,
                'text' => $this->clean($rule->text),
                'category' => $rule->category ?: 'health',
            ];
        }

        // Trik bhavas, on placement alone.
        foreach (self::ORDER as $name) {
            $planet = $facts['planets'][$name] ?? null;

            if (! $planet || ! in_array($planet['house'], [6, 8, 12], true)) {
                continue;
            }

            $facets = [
                'physical' => ['health', 'physically, the indications are'],
                'relations' => ['relationships', 'in relationships, it shows as'],
                'temperament' => ['mind', 'in temperament, it shows as'],
            ];

            foreach ($facets as $facet => [$category, $lead]) {
                $rule = $rules->first('trik_affliction', "{$name}:{$planet['house']}:{$facet}");

                if ($rule) {
                    $fired[] = [
                        'planet' => $name,
                        'house' => $planet['house'],
                        // Trik rows are noun lists, so they need a lead-in
                        // to read as a sentence rather than a fragment.
                        'text' => $lead.' '.$this->clean($rule->text),
                        'category' => $category,
                    ];
                }
            }
        }

        return $fired;
    }

    /** Resolve "the lord of the Nth" to the graha that actually rules it. */
    private function lordSubject(array $conditions, array $facts): ?string
    {
        if (! isset($conditions['lord_of'])) {
            return null;
        }

        foreach ((array) $conditions['lord_of'] as $house) {
            $sign = ($facts['lagna']['sign'] + $house - 1) % 12;
            $lord = Zodiac::SIGN_LORDS[$sign] ?? null;

            if ($lord && isset($facts['planets'][$lord])) {
                return $lord;
            }
        }

        return null;
    }

    /**
     * Grouped by graha. Grahas sharing a bhava are merged into one
     * lettered group, as a conjunction is read as a single condition.
     */
    private function analysis(array $facts, RuleRepository $rules, array $fired): array
    {
        $groups = [];
        $seen = [];

        foreach (self::ORDER as $name) {
            if (isset($seen[$name])) {
                continue;
            }

            $planet = $facts['planets'][$name] ?? null;

            if (! $planet) {
                continue;
            }

            // Everyone else sharing this bhava joins the same group.
            $companions = [];

            foreach (self::ORDER as $other) {
                $o = $facts['planets'][$other] ?? null;

                if ($o && $o['house'] === $planet['house']) {
                    $companions[] = $other;
                    $seen[$other] = true;
                }
            }

            $groups[] = $this->group($planet, $companions, $facts, $rules, $fired);
        }

        // Letter them A, B, C ... after grouping, not before.
        foreach ($groups as $i => $group) {
            $groups[$i]['letter'] = chr(65 + $i);
        }

        return $groups;
    }

    private function group(array $planet, array $companions, array $facts, RuleRepository $rules, array $fired): array
    {
        $names = array_map(fn ($n) => $facts['planets'][$n]['sanskrit'], $companions);
        $isConjunction = count($companions) > 1;

        $title = $isConjunction
            ? 'Conjunction of '.$this->listify($names).' ('.$this->ordinal($planet['house']).' House)'
            : 'Placement of '.$planet['sanskrit']
                .($planet['sanskrit'] === $planet['name'] ? '' : ' ('.$planet['name'].')');

        $heading = sprintf(
            '%s in the %s House (%s - %d)',
            $isConjunction ? $this->listify($names) : $planet['sanskrit'],
            $this->ordinal($planet['house']),
            $planet['sign_sanskrit'],
            $planet['sign'] + 1
        );

        $points = [];

        // What each graha in this group rules, stated once.
        foreach ($companions as $name) {
            $p = $facts['planets'][$name];
            $owned = $p['owns_houses'] ?? [];

            if ($owned !== []) {
                $points[] = sprintf(
                    '%s rules the %s and sits here in %s%s.',
                    $p['sanskrit'],
                    $this->listify(array_map(fn ($h) => $this->ordinal($h), $owned)),
                    $p['sign_name'],
                    $p['dignity'] !== 'neutral' ? ', where it is '.$p['dignity'] : ''
                );
            }
        }

        // Directional strength, where the corpus has something to say.
        foreach ($companions as $name) {
            $p = $facts['planets'][$name];
            $digbala = $p['digbala'];

            if (! $digbala['applicable']) {
                continue;
            }

            $band = match (true) {
                $digbala['strength'] >= 0.84 => 'full',
                $digbala['strength'] >= 0.6 => 'strong',
                $digbala['strength'] > 0.16 => 'weak',
                default => 'powerless',
            };

            if ($rule = $rules->first('digbala_planet', "{$p['name']}:{$band}")) {
                $points[] = $p['sanskrit'].' '.$this->clean($rule->text).'.';
            }
        }

        // Findings from the corpus that concern any graha in this group.
        foreach ($fired as $f) {
            if ($f['planet'] !== null && in_array($f['planet'], $companions, true)) {
                $points[] = 'According to your rules, '.$this->lowerFirst($f['text']).'.';
            }
        }

        return [
            'title' => $title,
            'heading' => $heading,
            'house' => $planet['house'],
            'points' => array_values(array_unique($points)),
        ];
    }

    /** Findings bucketed into the four standing life areas. */
    private function summary(array $fired): array
    {
        $buckets = array_fill_keys(array_keys(self::CATEGORIES), []);

        foreach ($fired as $f) {
            $category = array_key_exists($f['category'], $buckets) ? $f['category'] : 'health';
            $buckets[$category][] = $f['text'];
        }

        $out = [];

        foreach (self::CATEGORIES as $key => $label) {
            if ($buckets[$key] === []) {
                continue;
            }

            $out[] = [
                'key' => $key,
                'label' => $label,
                'points' => array_map(
                    fn ($t) => mb_strtoupper(mb_substr($t, 0, 1)).mb_substr($t, 1),
                    array_values(array_unique($buckets[$key]))
                ),
            ];
        }

        return $out;
    }

    private function clean(string $text): string
    {
        return rtrim(trim(preg_replace('/\s+/', ' ', $text)), '.');
    }

    private function lowerFirst(string $text): string
    {
        if (preg_match('/^[A-Z][a-z]+\s/', $text)) {
            return $text; // keep proper nouns such as Surya, Chandra
        }

        return mb_strtolower(mb_substr($text, 0, 1)).mb_substr($text, 1);
    }

    private function listify(array $items): string
    {
        if (count($items) === 1) {
            return $items[0];
        }

        $last = array_pop($items);

        return implode(', ', $items).' and '.$last;
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
