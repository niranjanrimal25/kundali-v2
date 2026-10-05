<?php

namespace App\Services\Astrology\Reading;

use App\Services\Astrology\Support\Zodiac;

/**
 * The first thing a reading must establish: what the Lagna is, and how
 * its lord is placed.
 *
 * The owner's method, implemented literally:
 *
 *   1. Identify the Lagna and therefore the Lagnesh.
 *   2. Find which bhava the Lagnesh occupies.
 *   3. The Lagnesh's difficulties manifest ONLY when it is negatively
 *      placed. Sitting in a dusthana (6, 8, 12), or lacking directional
 *      strength, is not by itself enough. The test is placement in a
 *      difficult or directionally weak bhava AND malefic company that
 *      dominates it.
 *   4. Where that holds, the qualities the Lagnesh governs are reduced
 *      and its bodily significations become vulnerable.
 *   5. Where it does not, the qualities stand and no disease is claimed.
 *
 * Directional strength follows Zodiac::DIGBALA_HOUSE. Being in the
 * opposite direction reduces efficacy; it is not inauspicious on its
 * own, which the output states explicitly.
 */
class LagneshAnalyser
{
    private const DUSTHANAS = [6, 8, 12];

    /** The north-direction bhavas the owner names as directionally weak. */
    private const NORTH = [4, 8, 12];

    private const MALEFICS = ['Saturn', 'Mars', 'Rahu', 'Ketu', 'Sun'];

    /** A graha within this many degrees of a sign boundary is weak. */
    private const LOW_DEGREE = 5.0;

    private array $taxonomy;

    public function __construct(RuleBase $ruleBase)
    {
        $this->taxonomy = $ruleBase->load('taxonomy');
    }

    public function analyse(array $payload): ?array
    {
        $lagnaSign = $payload['lagna']['sign'];
        $lagnesh = $payload['lagna']['lord'];
        $planet = $payload['planet'][$lagnesh] ?? null;

        if ($planet === null) {
            return null;
        }

        $house = $planet['house'];

        // --- the conditions, evaluated separately so the output can
        //     show its working rather than asserting a verdict ---
        $inDusthana = in_array($house, self::DUSTHANAS, true);
        $digheena = $this->lacksDirection($lagnesh, $house);
        $lowDegree = $planet['degree'] < self::LOW_DEGREE
            || $planet['degree'] > (30 - self::LOW_DEGREE);

        $malefics = array_values(array_intersect(
            $planet['influencedBy'],
            array_diff(self::MALEFICS, [$lagnesh])
        ));

        $enemies = $this->enemiesInfluencing($lagnesh, $planet, $payload);

        $weakDignity = in_array($planet['dignity'], ['enemy', 'debilitated'], true);
        $strongDignity = in_array($planet['dignity'], ['exalted', 'own', 'moolatrikona'], true);

        // Malefics must DOMINATE. A Lagnesh strong by sign holds its own
        // even in difficult company, which is why a strong dignity
        // cancels the affliction rather than merely softening it.
        $dominated = ($malefics !== [] || $enemies !== [])
            && ! $strongDignity;

        $afflicted = ($inDusthana || $digheena || $weakDignity || $lowDegree)
            && $dominated;

        return [
            'lagna' => [
                'signName' => $payload['lagna']['signName'],
                'signSanskrit' => $payload['lagna']['signSanskrit'],
                'signNumber' => $lagnaSign + 1,
            ],
            'lagnesh' => [
                'name' => $lagnesh,
                'sanskrit' => $planet['sanskrit'],
                'house' => $house,
                'signName' => $planet['signName'],
                'dignity' => $planet['dignity'],
                'degree' => round($planet['degree'], 2),
            ],
            'tests' => [
                'inDusthana' => $inDusthana,
                'directionallyWeak' => $digheena,
                'lowDegree' => $lowDegree,
                'weakDignity' => $weakDignity,
                'strongDignity' => $strongDignity,
                'maleficCompany' => $malefics,
                'enemyCompany' => $enemies,
                'dominated' => $dominated,
            ],
            'afflicted' => $afflicted,
            'verdict' => $this->verdict($planet, $lagnesh, $house, $afflicted, $inDusthana, $digheena, $lowDegree, $malefics, $enemies, $strongDignity),
            'qualities' => $this->qualities($lagnesh, $afflicted),
            'health' => $afflicted ? $this->health($lagnesh) : null,
        ];
    }

    /** Opposite of the graha's direction of strength. */
    private function lacksDirection(string $graha, int $house): bool
    {
        $strong = Zodiac::DIGBALA_HOUSE[$graha] ?? null;

        if ($strong === null) {
            return false;   // the nodes have no directional strength
        }

        $weak = (($strong + 6 - 1) % 12) + 1;

        return $house === $weak || in_array($house, self::NORTH, true) && $strong !== 4;
    }

    /** Natural enemies of the Lagnesh that share or aspect its bhava. */
    private function enemiesInfluencing(string $graha, array $planet, array $payload): array
    {
        $friends = Zodiac::FRIENDS[$graha] ?? [];
        $enemies = [];

        foreach ($planet['influencedBy'] as $other) {
            if ($other === $graha || in_array($other, $friends, true)) {
                continue;
            }

            if (in_array($other, ['Rahu', 'Ketu'], true)) {
                continue;   // already counted as malefics
            }

            $enemies[] = $other;
        }

        return $enemies;
    }

    private function verdict(
        array $planet, string $graha, int $house, bool $afflicted,
        bool $inDusthana, bool $digheena, bool $lowDegree,
        array $malefics, array $enemies, bool $strongDignity
    ): string {
        $name = $planet['sanskrit'];
        $ordinal = $this->ordinal($house);

        $placement = sprintf(
            '%s rules the Lagna and occupies the %s bhava in %s, where it is %s.',
            $name, $ordinal, $planet['signName'], $planet['dignity']
        );

        if (! $afflicted) {
            $reasons = [];

            if ($strongDignity) {
                $reasons[] = 'it is strong by sign';
            }

            if ($malefics === [] && $enemies === []) {
                $reasons[] = 'no malefic or enemy influences it';
            }

            $note = '';

            if ($digheena) {
                $note = ' It lacks directional strength here, which reduces its efficacy but is not inauspicious in itself.';
            }

            return $placement.' The conditions for an afflicted Lagnesh are not met, because '
                .($reasons === [] ? 'the placement is not a difficult one' : implode(' and ', $reasons))
                .', so the qualities it governs stand undiminished.'.$note;
        }

        $why = [];

        if ($inDusthana) {
            $why[] = 'it sits in a dusthana';
        }

        if ($digheena) {
            $why[] = 'it lacks directional strength';
        }

        if ($lowDegree) {
            $why[] = 'it is at a low degree';
        }

        $company = array_merge($malefics, $enemies);

        if ($company !== []) {
            $why[] = 'it is dominated by '.$this->listify(array_map(
                fn ($g) => Zodiac::PLANETS_SANSKRIT[$g] ?? $g,
                array_unique($company)
            ));
        }

        return $placement.' '.ucfirst($this->listify($why))
            .'. On this combination the qualities the Lagnesh governs are reduced, and its bodily significations become the vulnerable ones.';
    }

    private function qualities(string $graha, bool $afflicted): ?string
    {
        $items = $this->taxonomy['grahaAttributes'][$graha]['mind'] ?? [];

        if ($items === []) {
            return null;
        }

        $terms = array_map(fn ($i) => $i['term'], $items);

        return $afflicted
            ? 'Reduced or inconsistent: '.$this->listify($terms).'.'
            : 'Available and intact: '.$this->listify($terms).'.';
    }

    private function health(string $graha): ?string
    {
        $items = $this->taxonomy['grahaAttributes'][$graha]['body'] ?? [];

        if ($items === []) {
            return null;
        }

        return 'Watch '.$this->listify(array_map(fn ($i) => $i['term'], $items)).'.';
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

        // Several of these phrases contain "and" themselves, so joining
        // the last pair with another "and" reads badly. Use semicolons
        // when that is the case.
        $containsAnd = (bool) array_filter($items, fn ($i) => str_contains($i, ' and '));

        if ($containsAnd) {
            return implode('; ', $items);
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
