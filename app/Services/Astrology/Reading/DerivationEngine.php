<?php

namespace App\Services\Astrology\Reading;

/**
 * Composes Karakatwa against bhava significations, so no placement is
 * ever left blank when no explicit rule covers it.
 *
 * This is the layer that reproduces the reasoning in the owner's worked
 * example. "Mars in the 1st gives bodily heat and anger" is not written
 * anywhere in the manual; it is Mars's karakatwa (energy, anger, blood,
 * the fire element) composed with the 1st bhava (the body, the self).
 *
 * IMPORTANT: both halves come from owner-supplied data. The engine
 * contributes only the joining sentence, never new astrology. Where a
 * graha has no supplied karakatwa, nothing is derived for it rather
 * than something being invented.
 */
class DerivationEngine
{
    /**
     * @param  array  $karakatwa  rows from resources/rules/karakatwa.json
     * @return list<array> findings in the same shape RuleEngine returns
     */
    /** Sentence frames, per locale. Only these are translated; the
     *  karakatwa and bhava texts come from the rule base already
     *  translated. */
    private const FRAMES = [
        'en' => [
            'body' => '%s governs %s. Sitting in the %s bhava, which rules %s, it draws those parts of the body into the affairs of this house.',
            'mind' => '%s carries %s. In the %s bhava these qualities are expressed through %s.',
            'relations' => '%s stands for %s, and its placement in the %s bhava colours how those relationships run.',
            'other' => '%s also signifies %s, which the %s bhava brings into play.',
        ],
        'ne' => [
            'body' => '%s ले %s लाई प्रतिनिधित्व गर्दछ। %s भावमा बसेकाले, जुन %s को कारक हो, यी शारीरिक अंगहरू यस भावका विषयहरूसँग जोडिन्छन्।',
            'mind' => '%s ले %s बोक्दछ। %s भावमा यी गुणहरू %s मार्फत प्रकट हुन्छन्।',
            'relations' => '%s ले %s लाई जनाउँछ, र %s भावमा रहेको यसको स्थितिले ती सम्बन्धहरूलाई प्रभाव पार्दछ।',
            'other' => '%s ले %s लाई पनि जनाउँछ, जसलाई %s भावले सक्रिय बनाउँछ।',
        ],
    ];

    public function derive(array $payload, array $karakatwa, array $explicitSubjects = [], string $locale = 'en'): array
    {
        $frames = self::FRAMES[$locale] ?? self::FRAMES['en'];
        $vocab = new Vocabulary($locale);

        $bySubject = [];

        foreach ($karakatwa as $row) {
            $bySubject[$row['subject']][$row['facet']] = $row;
        }

        $findings = [];

        foreach (ChartPayload::GRAHAS as $graha) {
            $planet = $payload['planet'][$graha] ?? null;

            if (! $planet || ! isset($bySubject[$graha])) {
                continue;   // no supplied karakatwa: derive nothing
            }

            $house = $payload['house'][$planet['house']];

            // Body + house -> what is physically touched.
            if (isset($bySubject[$graha]['body'])) {
                $findings[] = $this->finding(
                    $graha,
                    'health',
                    "KARAKA_DERIVED_{$graha}_BODY_H{$planet['house']}",
                    sprintf(
                        $frames['body'],
                        $vocab->graha($graha, $planet['sanskrit']),
                        $this->trim($bySubject[$graha]['body']['text']),
                        $vocab->ordinal($planet['house']),
                        $vocab->significations($planet['house'], $house['significations'])
                    )
                );
            }

            // Qualities + house -> temperament expressed through the house.
            if (isset($bySubject[$graha]['qualities'])) {
                $findings[] = $this->finding(
                    $graha,
                    'mind',
                    "KARAKA_DERIVED_{$graha}_MIND_H{$planet['house']}",
                    sprintf(
                        $frames['mind'],
                        $vocab->graha($graha, $planet['sanskrit']),
                        $this->trim($bySubject[$graha]['qualities']['text']),
                        $vocab->ordinal($planet['house']),
                        $vocab->houseLabel($planet['house'], mb_strtolower($house['label']))
                    )
                );
            }

            // Relations + house.
            if (isset($bySubject[$graha]['relations'])) {
                $findings[] = $this->finding(
                    $graha,
                    'relationships',
                    "KARAKA_DERIVED_{$graha}_REL_H{$planet['house']}",
                    sprintf(
                        $frames['relations'],
                        $vocab->graha($graha, $planet['sanskrit']),
                        $this->trim($bySubject[$graha]['relations']['text']),
                        $this->ordinal($planet['house'])
                    )
                );
            }

            // Other significations -> worldly matters.
            if (isset($bySubject[$graha]['other'])) {
                $findings[] = $this->finding(
                    $graha,
                    'career',
                    "KARAKA_DERIVED_{$graha}_OTHER_H{$planet['house']}",
                    sprintf(
                        $frames['other'],
                        $vocab->graha($graha, $planet['sanskrit']),
                        $this->trim($bySubject[$graha]['other']['text']),
                        $this->ordinal($planet['house'])
                    )
                );
            }
        }

        return $findings;
    }

    private function finding(string $subject, string $category, string $id, string $text): array
    {
        return [
            'id' => $id,
            'subject' => $subject,
            'category' => $category,
            'priority' => 20,          // always below an explicit rule
            'source' => 'Derived from supplied Karakatwa and bhava significations',
            'derived' => true,
            'title' => null,
            'text' => $text,
        ];
    }

    private function trim(string $text): string
    {
        return rtrim(trim(preg_replace('/\s+/', ' ', $text)), '.');
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
