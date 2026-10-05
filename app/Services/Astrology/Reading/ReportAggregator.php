<?php

namespace App\Services\Astrology\Reading;

/**
 * STEP 5 — Report generation.
 *
 * Merges triggered rules into the standard markdown template:
 *
 *   1. Chart Placement Overview      occupied bhavas only
 *   2. Detailed Analysis Based On Your Rules
 *                                    grouped by graha, conjunctions merged
 *   3. Summary of Key Outcomes       bucketed by life area
 *
 * Returns markdown, plus the same content as structured arrays so the
 * web view and the PDF can render it without parsing text back out.
 */
class ReportAggregator
{
    public const CATEGORY_LABELS = [
        'health' => 'Health & Body',
        'mind' => 'Mind & Temperament',
        'relationships' => 'Relationships',
        'career' => 'Career & Finances',
    ];

    /**
     * @param  list<array>  $findings  from RuleEngine + DerivationEngine
     */
    public function build(array $payload, array $findings): array
    {
        $placements = $this->placements($payload);
        $groups = $this->groups($payload, $findings);
        $summary = $this->summary($findings);

        return [
            'placements' => $placements,
            'groups' => $groups,
            'summary' => $summary,
            'markdown' => $this->markdown($placements, $groups, $summary),
            'stats' => [
                'explicit' => count(array_filter($findings, fn ($f) => ! $f['derived'])),
                'derived' => count(array_filter($findings, fn ($f) => $f['derived'])),
            ],
        ];
    }

    /** 1. Occupied bhavas only. */
    private function placements(array $payload): array
    {
        $out = [];

        foreach ($payload['house'] as $house) {
            if ($house['occupants'] === []) {
                continue;
            }

            $out[] = [
                'house' => $house['number'],
                'ordinal' => $this->ordinal($house['number']),
                'isLagna' => $house['number'] === 1,
                'signName' => $house['signName'],
                'signSanskrit' => $house['signSanskrit'],
                'signNumber' => $house['signNumber'],
                'grahas' => array_map(fn ($g) => [
                    'name' => $g,
                    'sanskrit' => $payload['planet'][$g]['sanskrit'],
                    'combust' => $payload['planet'][$g]['combust'],
                    'retrograde' => $payload['planet'][$g]['retrograde'],
                ], $house['occupants']),
            ];
        }

        return $out;
    }

    /** 2. One lettered group per bhava that holds grahas. */
    private function groups(array $payload, array $findings): array
    {
        $groups = [];
        $letter = 0;

        foreach ($payload['house'] as $house) {
            if ($house['occupants'] === []) {
                continue;
            }

            $occupants = $house['occupants'];
            $names = array_map(fn ($g) => $payload['planet'][$g]['sanskrit'], $occupants);

            $points = [];

            foreach ($findings as $f) {
                if ($f['subject'] !== null && ! in_array($f['subject'], $occupants, true)) {
                    continue;
                }

                // A rule with no subject belongs to the bhava its lord occupies.
                if ($f['subject'] === null) {
                    continue;
                }

                $points[] = [
                    'text' => $f['text'],
                    'derived' => $f['derived'],
                    'source' => $f['source'],
                ];
            }

            $explicit = array_values(array_filter($points, fn ($p) => ! $p['derived']));
            $derived = array_values(array_filter($points, fn ($p) => $p['derived']));

            $groups[] = [
                'letter' => chr(65 + $letter++),
                'title' => count($occupants) > 1
                    ? 'Conjunction of '.$this->listify($names).' ('.$this->ordinal($house['number']).' House)'
                    : 'Placement of '.$names[0].' ('.$occupants[0].')',
                'heading' => sprintf(
                    '%s in the %s House (%s - %d)',
                    $this->listify($names),
                    $this->ordinal($house['number']),
                    $house['signSanskrit'],
                    $house['signNumber']
                ),
                'house' => $house['number'],
                'points' => $points,
                // Explicit rule matches are shown by default; the derived
                // Karakatwa breakdown sits behind a disclosure so a long
                // chart does not bury the findings that actually matched.
                'explicit' => $explicit,
                'derived' => $derived,
            ];
        }

        // Subject-less rules (lord placements) get their own closing group.
        $lordPoints = [];

        foreach ($findings as $f) {
            if ($f['subject'] === null) {
                $lordPoints[] = [
                    'text' => $f['text'],
                    'derived' => $f['derived'],
                    'source' => $f['source'],
                ];
            }
        }

        if ($lordPoints !== []) {
            $groups[] = [
                'letter' => chr(65 + $letter),
                'title' => 'Bhava Lord Placements',
                'heading' => 'Rules that depend on where a bhava lord sits',
                'house' => null,
                'points' => $lordPoints,
                'explicit' => array_values(array_filter($lordPoints, fn ($p) => ! $p['derived'])),
                'derived' => array_values(array_filter($lordPoints, fn ($p) => $p['derived'])),
            ];
        }

        return $groups;
    }

    /** 3. Findings bucketed by life area. */
    private function summary(array $findings): array
    {
        $buckets = array_fill_keys(array_keys(self::CATEGORY_LABELS), []);

        foreach ($findings as $f) {
            // The summary reports what the rule base actually matched.
            // Derived Karakatwa would swamp it, and is available in full
            // under each placement.
            if ($f['derived']) {
                continue;
            }

            $key = array_key_exists($f['category'], $buckets) ? $f['category'] : 'health';
            $buckets[$key][] = $f['text'];
        }

        $out = [];

        foreach (self::CATEGORY_LABELS as $key => $label) {
            if ($buckets[$key] === []) {
                continue;
            }

            $out[] = [
                'key' => $key,
                'label' => $label,
                'points' => array_values(array_unique($buckets[$key])),
            ];
        }

        return $out;
    }

    private function markdown(array $placements, array $groups, array $summary): string
    {
        $md = "## 1. Chart Placement Overview\n\n";

        foreach ($placements as $p) {
            $grahas = implode(', ', array_map(
                fn ($g) => '**'.$g['sanskrit'].'**'.($g['combust'] ? '\*' : '').($g['retrograde'] ? ' ℞' : ''),
                $p['grahas']
            ));

            $md .= sprintf(
                "- **%s%s House:** %s (%s - %d) with %s\n",
                $p['isLagna'] ? 'Ascendant / Lagna — ' : '',
                $p['ordinal'],
                $p['signName'],
                $p['signSanskrit'],
                $p['signNumber'],
                $grahas
            );
        }

        $md .= "\n## 2. Detailed Analysis Based On Your Rules\n\n";

        foreach ($groups as $g) {
            $md .= "**{$g['letter']}. {$g['title']}**\n\n";
            $md .= "- **{$g['heading']}:**\n";

            if ($g['explicit'] === []) {
                $md .= "  - No explicit rule in the rule base matches this placement.\n";
            }

            foreach ($g['explicit'] as $point) {
                $md .= '  - '.$point['text']."\n";
            }

            if ($g['derived'] !== []) {
                $md .= "\n  <details>\n  <summary>View Detailed Karakatwa &amp; House Breakdown</summary>\n\n";

                foreach ($g['derived'] as $point) {
                    $md .= '  - '.$point['text']."\n";
                }

                $md .= "\n  </details>\n";
            }

            $md .= "\n";
        }

        if ($summary !== []) {
            $md .= "## 3. Summary of Key Outcomes\n\n";

            foreach ($summary as $i => $bucket) {
                $md .= ($i + 1).". **{$bucket['label']}**\n";

                foreach ($bucket['points'] as $point) {
                    $md .= '   - '.$point."\n";
                }

                $md .= "\n";
            }
        }

        return $md;
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
