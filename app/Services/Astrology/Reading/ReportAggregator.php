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
    private string $locale = 'en';

    private Vocabulary $vocab;

    private const LABELS = [
        'en' => [
            'conjunction' => 'Conjunction of %s (%s House)',
            'placement' => 'Placement of %s (%s)',
            'heading' => '%s in the %s House (%s - %d)',
            'lords' => 'Bhava Lord Placements',
            'lordsHeading' => 'Rules that depend on where a bhava lord sits',
            's1' => '1. Chart Placement Overview',
            's2' => '2. Detailed Analysis Based On Your Rules',
            's3' => '3. Summary of Key Outcomes',
            'none' => 'No rule in the current rule base covers this placement.',
            'lagna' => 'Ascendant / Lagna',
            'with' => 'with',
        ],
        'ne' => [
            'conjunction' => '%s को युति (%s भाव)',
            'placement' => '%s को स्थिति (%s)',
            'heading' => '%s %s भावमा (%s - %d)',
            'lords' => 'भावेशको स्थिति',
            'lordsHeading' => 'भावेश कहाँ बसेको छ भन्नेमा आधारित नियमहरू',
            's1' => '१. ग्रह स्थिति सारांश',
            's2' => '२. तपाईंका नियम अनुसार विस्तृत विश्लेषण',
            's3' => '३. मुख्य नतिजाहरूको सारांश',
            'none' => 'हालको नियम आधारमा यस स्थितिलाई समेट्ने कुनै नियम छैन।',
            'lagna' => 'लग्न',
            'with' => 'सँग',
        ],
    ];

    private const CATEGORY_LABELS_NE = [
        'health' => 'स्वास्थ्य र शरीर',
        'mind' => 'मन र स्वभाव',
        'relationships' => 'सम्बन्धहरू',
        'career' => 'पेसा र अर्थ',
    ];

    private function label(string $key): string
    {
        return self::LABELS[$this->locale][$key] ?? self::LABELS['en'][$key];
    }

    private function categoryLabel(string $key): string
    {
        return $this->locale === 'ne'
            ? (self::CATEGORY_LABELS_NE[$key] ?? self::CATEGORY_LABELS[$key])
            : self::CATEGORY_LABELS[$key];
    }

    public const CATEGORY_LABELS = [
        'health' => 'Health & Body',
        'mind' => 'Mind & Temperament',
        'relationships' => 'Relationships',
        'career' => 'Career & Finances',
    ];

    /**
     * @param  list<array>  $findings  from RuleEngine + DerivationEngine
     */
    public function build(array $payload, array $findings, string $locale = 'en'): array
    {
        $this->locale = $locale;
        $this->vocab = new Vocabulary($locale);

        $placements = $this->placements($payload);
        $groups = $this->groups($payload, $findings);
        $summary = $this->summary($findings);

        return [
            'placements' => $placements,
            'groups' => $groups,
            'summary' => $summary,
            'markdown' => $this->markdown($placements, $groups, $summary),
            'labels' => [
                's1' => $this->label('s1'),
                's2' => $this->label('s2'),
                's3' => $this->label('s3'),
                'lagna' => $this->label('lagna'),
                'house' => $this->vocab->houseWord(),
                'with' => $this->label('with'),
                'none' => $this->label('none'),
            ],
            'locale' => $locale,
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
                'ordinal' => $this->vocab->ordinal($house['number']),
                'isLagna' => $house['number'] === 1,
                'signName' => $this->vocab->sign($house['signName']),
                'signSanskrit' => $house['signSanskrit'],
                'signNumber' => $house['signNumber'],
                'grahas' => array_map(fn ($g) => [
                    'name' => $g,
                    'sanskrit' => $this->vocab->graha($g, $payload['planet'][$g]['sanskrit']),
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
            $names = array_map(fn ($g) => $this->vocab->graha($g, $payload['planet'][$g]['sanskrit']), $occupants);

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

            $groups[] = [
                'letter' => chr(65 + $letter++),
                'title' => count($occupants) > 1
                    ? sprintf($this->label('conjunction'), $this->listify($names), $this->vocab->ordinal($house['number']))
                    : sprintf($this->label('placement'), $names[0], $this->vocab->graha($occupants[0], $occupants[0])),
                'heading' => sprintf(
                    $this->label('heading'),
                    $this->listify($names),
                    $this->vocab->ordinal($house['number']),
                    $house['signSanskrit'],
                    $house['signNumber']
                ),
                'house' => $house['number'],
                'points' => $points,
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
                'title' => $this->label('lords'),
                'heading' => $this->label('lordsHeading'),
                'house' => null,
                'points' => $lordPoints,
            ];
        }

        return $groups;
    }

    /** 3. Findings bucketed by life area. */
    private function summary(array $findings): array
    {
        $buckets = array_fill_keys(array_keys(self::CATEGORY_LABELS), []);

        foreach ($findings as $f) {
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
                'label' => $this->categoryLabel($key),
                'points' => array_values(array_unique($buckets[$key])),
            ];
        }

        return $out;
    }

    private function markdown(array $placements, array $groups, array $summary): string
    {
        $md = '## '.$this->label('s1')."\n\n";

        foreach ($placements as $p) {
            $grahas = implode(', ', array_map(
                fn ($g) => '**'.$g['sanskrit'].'**'.($g['combust'] ? '\*' : '').($g['retrograde'] ? ' ℞' : ''),
                $p['grahas']
            ));

            $md .= sprintf(
                "- **%s%s %s:** %s (%s - %d) %s %s\n",
                $p['isLagna'] ? $this->label('lagna').' — ' : '',
                $p['ordinal'],
                $this->vocab->houseWord(),
                $p['signName'],
                $p['signSanskrit'],
                $p['signNumber'],
                $this->label('with'),
                $grahas
            );
        }

        $md .= "\n## ".$this->label('s2')."\n\n";

        foreach ($groups as $g) {
            $md .= "**{$g['letter']}. {$g['title']}**\n\n";
            $md .= "- **{$g['heading']}:**\n";

            if ($g['points'] === []) {
                $md .= '  - '.$this->label('none')."\n";
            }

            foreach ($g['points'] as $point) {
                $md .= '  - '.$point['text']."\n";
            }

            $md .= "\n";
        }

        if ($summary !== []) {
            $md .= '## '.$this->label('s3')."\n\n";

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
