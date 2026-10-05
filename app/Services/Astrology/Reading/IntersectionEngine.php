<?php

namespace App\Services\Astrology\Reading;

/**
 * Replaces the Cartesian "kitchen sink" derivation.
 *
 * The old DerivationEngine emitted every signification of every graha
 * against every bhava it occupied, which predicted kidney trouble from
 * a 3rd-house placement. This applies three filters first.
 *
 * STEP 1 - Attribute intersection
 *   A graha attribute is reported only when its tags overlap the tags
 *   of the bhava it sits in. Venus's kidneys carry the tag "kidneys",
 *   which belongs to the 7th, not the 3rd, so it is dropped there.
 *
 * STEP 2 - Dignity and functional status gate
 *   A graha in its own, exalted or moolatrikona sign is protected:
 *   high-severity warnings are suppressed, because the classical
 *   reading is that a strong graha does not break the house it rules.
 *   Rahu with a strong co-tenant amplifies rather than destroys, so
 *   its severe items are suppressed and its ambition traits promoted.
 *
 * STEP 3 - De-duplication and categorised synthesis
 *   Everything surviving for one bhava is merged into a single block
 *   with three bullets: health focus, mind and temperament, and key
 *   relationships. Terms repeated by two grahas appear once.
 */
class IntersectionEngine
{
    private array $taxonomy;

    public function __construct(RuleBase $ruleBase)
    {
        $this->taxonomy = $ruleBase->load('taxonomy');
    }

    /**
     * One consolidated finding set per occupied bhava.
     *
     * @return array<int, array{health:?string, mind:?string, people:?string, notes:list<string>}>
     */
    public function forHouses(array $payload): array
    {
        $out = [];

        foreach ($payload['house'] as $number => $house) {
            if ($house['occupants'] === []) {
                continue;
            }

            $domains = $this->taxonomy['houseDomains'][(string) $number] ?? null;

            if ($domains === null) {
                continue;
            }

            $block = $this->synthesise($number, $house, $domains, $payload);

            if ($block !== null) {
                $out[$number] = $block;
            }
        }

        return $out;
    }

    private function synthesise(int $number, array $house, array $domains, array $payload): ?array
    {
        $bodyTags = $domains['body'] ?? [];
        $themeTags = $domains['theme'] ?? [];
        $peopleTags = $domains['people'] ?? [];

        $health = [];
        $mind = [];
        $people = [];
        $notes = [];

        $anyStrong = false;
        $suppressed = [];

        foreach ($house['occupants'] as $graha) {
            $planet = $payload['planet'][$graha];
            $attributes = $this->taxonomy['grahaAttributes'][$graha] ?? null;

            if ($attributes === null) {
                continue;
            }

            $tier = $this->taxonomy['dignityTiers'][$planet['dignity']] ?? 'stable';

            if ($tier === 'strong') {
                $anyStrong = true;
            }
        }

        foreach ($house['occupants'] as $graha) {
            $planet = $payload['planet'][$graha];
            $attributes = $this->taxonomy['grahaAttributes'][$graha] ?? null;

            if ($attributes === null) {
                continue;
            }

            $tier = $this->taxonomy['dignityTiers'][$planet['dignity']] ?? 'stable';

            // STEP 2. A strong graha is protected. A node sharing a bhava
            // with a strong graha amplifies it rather than wrecking it.
            $protected = $tier === 'strong'
                || (in_array($graha, ['Rahu', 'Ketu'], true) && $anyStrong);

            foreach ($attributes['body'] ?? [] as $item) {
                if (! $this->overlaps($item['tags'], $bodyTags)) {
                    continue;   // STEP 1: not governed by this bhava
                }

                if ($protected && ($item['severity'] ?? 'normal') === 'high') {
                    $suppressed[] = $item['term'];

                    continue;   // STEP 2: severe warning gated out
                }

                // STEP 3: de-duplicate on the canonical key, so two
                // grahas naming the same organ yield one phrase.
                $health[$item['key'] ?? $item['term']] ??= $item['term'];
            }

            foreach ($attributes['mind'] ?? [] as $item) {
                if (! $this->overlaps($item['tags'], array_merge($themeTags, $bodyTags))) {
                    continue;
                }

                // A protected graha does not contribute its negative traits.
                if ($protected && ($item['polarity'] ?? 'mixed') === 'negative') {
                    continue;
                }

                $mind[$item['key'] ?? $item['term']] ??= $item['term'];
            }

            foreach ($attributes['people'] ?? [] as $item) {
                if (! $this->overlaps($item['tags'], $peopleTags)) {
                    continue;
                }

                $people[$item['key'] ?? $item['term']] ??= $item['term'];
            }

            if ($protected && $tier === 'strong') {
                $notes[] = sprintf(
                    '%s is strong in %s, which protects this bhava.',
                    $planet['sanskrit'],
                    $planet['signName']
                );
            }
        }

        if ($health === [] && $mind === [] && $people === []) {
            return null;
        }

        return [
            'health' => $this->healthSentence(array_values($health), $suppressed, $anyStrong),
            'mind' => $this->mindSentence(array_values($mind)),
            'people' => $this->peopleSentence(array_values($people), $house),
            'notes' => array_values(array_unique($notes)),
            'suppressed' => array_values(array_unique($suppressed)),
        ];
    }

    private function healthSentence(array $terms, array $suppressed, bool $anyStrong): ?string
    {
        if ($terms === []) {
            return null;
        }

        $text = 'Sensitivity concentrates in '.$this->listify($terms).'.';

        if ($suppressed !== [] && $anyStrong) {
            $text .= ' Severe indications for '.$this->listify(array_unique($suppressed))
                .' are suppressed, because a graha strong in its own sign does not break the bhava it occupies.';
        }

        return $text;
    }

    private function mindSentence(array $terms): ?string
    {
        if ($terms === []) {
            return null;
        }

        return ucfirst($this->listify($terms)).'.';
    }

    private function peopleSentence(array $terms, array $house): ?string
    {
        if ($terms === []) {
            return null;
        }

        return 'Attention falls on '.$this->listify($terms).'.';
    }

    private function overlaps(array $a, array $b): bool
    {
        return array_intersect($a, $b) !== [];
    }

    private function listify(array $items): string
    {
        $items = array_values(array_filter($items));

        if ($items === []) {
            return '';
        }

        if (count($items) === 1) {
            return $items[0];
        }

        $last = array_pop($items);

        return implode(', ', $items).' and '.$last;
    }
}
