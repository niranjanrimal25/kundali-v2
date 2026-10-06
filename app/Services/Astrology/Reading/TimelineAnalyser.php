<?php

namespace App\Services\Astrology\Reading;

use App\Services\Astrology\Support\Zodiac;

/**
 * Restores the dasha, yoga, dosha and transit sections that the rule
 * rebuild left orphaned. The detectors never stopped running; nothing
 * was reading them.
 *
 * Per the owner's direction:
 *
 *   Dasha  - current Mahadasha and Antardasha with dates, flagging
 *            whether the operating lord is the Lagnesh, a Trik lord,
 *            or an afflicted graha.
 *   Yogas  - printed as positive strength multipliers that balance
 *            afflictions, not as isolated trivia.
 *   Doshas - Mangal with its cancellations, Kaal Sarpa, Grahan, and
 *            the running Saturn transit phase.
 */
class TimelineAnalyser
{
    private const TRIK = [6, 8, 12];

    private const MALEFICS = ['Saturn', 'Mars', 'Rahu', 'Ketu', 'Sun'];

    /** @return array{dasha:?array, yogas:?array, doshas:?array} */
    public function analyse(array $payload): array
    {
        return [
            'dasha' => $this->dasha($payload),
            'yogas' => $this->yogas($payload),
            'doshas' => $this->doshas($payload),
        ];
    }

    private function dasha(array $payload): ?array
    {
        $current = $payload['dasha']['current'] ?? null;

        if (! $current || empty($current['mahadasha'])) {
            return null;
        }

        $maha = $current['mahadasha'];
        $antar = $current['antardasha'] ?? null;

        return [
            'mahadasha' => $this->period($maha, $payload),
            'antardasha' => $antar ? $this->period($antar, $payload) : null,
            'balance' => $payload['dasha']['balance_at_birth'] ?? null,
        ];
    }

    /** One dasha period, with the standing of its lord spelled out. */
    private function period(array $period, array $payload): array
    {
        $lord = $period['lord'];
        $planet = $payload['planet'][$lord] ?? null;

        $roles = [];
        $afflicted = false;

        if ($planet) {
            if ($lord === $payload['lagna']['lord']) {
                $roles[] = 'the Lagnesh';
            }

            $trikLordships = array_intersect($planet['lordOf'], self::TRIK);

            foreach ($trikLordships as $house) {
                $roles[] = 'lord of the '.$this->ordinal($house);
            }

            $afflicted = $planet['afflicted'] ?? false;
        }

        $standing = match (true) {
            $roles === [] && ! $afflicted => 'It carries no special burden in this chart.',
            $roles === [] && $afflicted => 'It is afflicted in this chart, so the period runs harder than the placement alone suggests.',
            default => 'It is '.$this->listify($roles).'.'
                .($afflicted ? ' It is also afflicted, which weighs on the period.' : ''),
        };

        return [
            'lord' => $lord,
            'sanskrit' => Zodiac::PLANETS_SANSKRIT[$lord] ?? $lord,
            'start' => $period['start'] ?? null,
            'end' => $period['end'] ?? null,
            'house' => $planet['house'] ?? null,
            'signName' => $planet['signName'] ?? null,
            'isLagnesh' => $lord === $payload['lagna']['lord'],
            'trikLord' => array_values(array_intersect($planet['lordOf'] ?? [], self::TRIK)),
            'afflicted' => $afflicted,
            'standing' => $standing,
        ];
    }

    /**
     * Yogas are reported as counterweights. Where afflictions exist,
     * the yoga is what offsets them, so the two are stated together.
     */
    private function yogas(array $payload): ?array
    {
        $yogas = $payload['yogas'] ?? [];

        if ($yogas === []) {
            return null;
        }

        $items = [];

        foreach ($yogas as $yoga) {
            $items[] = [
                'name' => $yoga['name'],
                'strength' => $yoga['strength'] ?? 'moderate',
                'basis' => $yoga['basis'] ?? '',
            ];
        }

        $strong = count(array_filter($items, fn ($y) => $y['strength'] === 'strong'));

        return [
            'items' => $items,
            'note' => sprintf(
                '%d yoga%s present%s. These act as strength in the chart and offset affliction elsewhere rather than standing alone.',
                count($items),
                count($items) === 1 ? '' : 's',
                $strong > 0 ? ', '.$strong.' of them strongly formed' : ''
            ),
        ];
    }

    private function doshas(array $payload): ?array
    {
        $doshas = $payload['doshas'] ?? [];
        $sadeSati = $payload['transits']['sade_sati'] ?? null;

        if ($doshas === [] && $sadeSati === null) {
            return null;
        }

        $items = [];

        foreach ($doshas as $dosha) {
            $items[] = [
                'name' => $dosha['name'],
                'basis' => $dosha['basis'] ?? '',
                'cancelled' => (bool) ($dosha['cancelled'] ?? false),
                'cancellation' => $dosha['cancellation'] ?? null,
                'severity' => $dosha['severity'] ?? 'present',
            ];
        }

        $transit = null;

        if ($sadeSati) {
            $transit = [
                'name' => $sadeSati['name'],
                'phase' => $sadeSati['phase'] ?? null,
                'basis' => $sadeSati['basis'] ?? '',
                'saturnSign' => $payload['transits']['saturn_sign'] ?? null,
                'computedAt' => $payload['transits']['computed_at'] ?? null,
            ];
        }

        return ['items' => $items, 'transit' => $transit];
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
