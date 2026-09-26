<?php

namespace App\Services\Astrology\Interpretation;

use App\Models\Kundali;
use App\Models\Reading;
use App\Services\Astrology\KundaliService;
use App\Services\Astrology\Support\Zodiac;
use Carbon\Carbon;

/**
 * Layer 3 — the interpretation layer.
 *
 * Reads ChartFacts (never astronomy) and produces the narrative report.
 *
 * PILOT SCOPE: Overview plus the 1st, 7th and 10th Bhavas. The structure
 * below generalises to all twelve houses; extending it is a matter of
 * seeding more rules, not writing more code.
 */
class ReadingGenerator
{
    /** Houses covered by the current rule corpus. */
    public const COVERED_HOUSES = [1, 7, 10];

    public function __construct(
        private readonly KundaliService $kundaliService,
    ) {}

    public function forKundali(Kundali $kundali, string $locale = 'en', bool $force = false): array
    {
        $existing = $kundali->reading($locale);

        if (! $force && $existing !== null) {
            return $existing->sections;
        }

        $facts = $this->kundaliService->facts($kundali);
        $sections = $this->generate($facts, $kundali, $locale);

        Reading::updateOrCreate(
            ['kundali_id' => $kundali->id, 'locale' => $locale],
            ['sections' => $sections],
        );

        $kundali->unsetRelation('readings');

        return $sections;
    }

    public function generate(array $facts, ?Kundali $kundali = null, string $locale = 'en'): array
    {
        $rules = new RuleRepository($locale);

        // Seed narrative variation from the chart itself, so the prose is
        // stable for one person but differs between people.
        $seed = $facts['lagna']['sign'].':'.$facts['meta']['julian_day'];

        $sections = [];
        $sections[] = $this->overview($facts, $rules, $seed, $kundali);

        foreach (self::COVERED_HOUSES as $house) {
            $sections[] = $this->houseSection($house, $facts, $rules, $seed);
        }

        $sections[] = $this->dashaSection($facts, $seed);

        return array_values(array_filter($sections));
    }

    /** Opening section: Lagna, Rashi, Nakshatra, overall character. */
    private function overview(array $facts, RuleRepository $rules, string $seed, ?Kundali $kundali): array
    {
        $composer = new NarrativeComposer($seed.'overview');

        $lagna = $facts['lagna'];
        $moon = $facts['moon'];
        $lagnesh = $facts['planets'][$lagna['lord']];

        $paragraphs = [];

        // 1. The Lagna statement — degree, sign, nature.
        $lagnaRule = $rules->first('lagna_sign', (string) $lagna['sign']);

        $opening = sprintf(
            'Your Lagna falls in %s (%s), rising at %s, %s.',
            $lagna['sign_sanskrit'],
            $lagna['sign_name'],
            $lagna['degree_formatted'],
            $lagnaRule ? lcfirst($this->trimText($lagnaRule->text)) : 'which sets the tone for the whole chart'
        );

        $paragraphs[] = trim($opening);

        // 2. Where the Lagna lord sits — the single most telling fact.
        $lordRule = $rules->first('lord_in_house', '1:'.$lagnesh['house']);

        if ($lordRule) {
            $lordText = sprintf(
                'Your Lagnesh %s, the ruler of the ascendant, is %s',
                $lagnesh['sanskrit'],
                $this->trimText($lordRule->text)
            );

            $modifiers = $this->modifiersFor($lagnesh, $rules);

            $paragraphs[] = $composer->planetSentence($lagnesh['name'], $lordText, $modifiers);
        }

        // 3. Moon: mind, emotion, nakshatra temperament.
        $paragraphs[] = sprintf(
            'Chandra occupies %s, making %s your Janma Rashi, and falls in the nakshatra %s (pada %d), ruled by %s. '
            .'Your emotional nature and habitual cast of mind are shaped more by this placement than by the ascendant, '
            .'and the %s gana of this nakshatra colours how you instinctively meet other people.',
            $moon['sign_name'],
            $moon['rashi'],
            $moon['nakshatra']['name'],
            $moon['nakshatra']['pada'],
            Zodiac::PLANETS_SANSKRIT[$moon['nakshatra']['lord']] ?? $moon['nakshatra']['lord'],
            strtolower($moon['nakshatra']['gana'])
        );

        // 4. Structural summary: how the chart is weighted.
        $paragraphs[] = $this->chartBalance($facts);

        return [
            'key' => 'overview',
            'title' => 'Overview',
            'subtitle' => sprintf(
                '%s Lagna · %s Rashi · %s Nakshatra',
                $lagna['sign_name'],
                $moon['sign_name'],
                $moon['nakshatra']['name']
            ),
            'paragraphs' => array_values(array_filter($paragraphs)),
        ];
    }

    /**
     * A single bhava: sign on the house, its lord's placement,
     * the planets sitting in it, and what aspects it.
     */
    private function houseSection(int $house, array $facts, RuleRepository $rules, string $seed): array
    {
        $composer = new NarrativeComposer($seed.'h'.$house);
        $data = $facts['houses'][$house];
        $meta = Zodiac::HOUSES[$house];

        $fragments = [];

        // 1. The sign on the house.
        $signRule = $rules->first('house_sign', "{$house}:{$data['sign']}");

        $intro = sprintf(
            'The %s Bhava, governing %s, carries %s — %s sign of the %s element — ruled by %s.',
            $this->ordinal($house),
            str_replace(' & ', ' and ', strtolower($meta['english'])),
            $data['sign_sanskrit'].' ('.$data['sign_name'].')',
            strtolower($data['modality']) === 'dual' ? 'a dual' : 'a '.strtolower($data['modality']),
            strtolower($data['element']),
            Zodiac::PLANETS_SANSKRIT[$data['lord']] ?? $data['lord']
        );

        $paragraphs = [$intro];

        if ($signRule) {
            $fragments[] = ['text' => $signRule->text, 'polarity' => $signRule->polarity, 'weight' => $signRule->weight];
        }

        // 2. The house lord's placement.
        $lordPlanet = $facts['planets'][$data['lord']];
        $lordRule = $rules->first('lord_in_house', "{$house}:{$lordPlanet['house']}");

        if ($lordRule) {
            $fragments[] = [
                'text' => sprintf(
                    'Its lord %s is %s',
                    $lordPlanet['sanskrit'],
                    $this->trimText($lordRule->text)
                ),
                'polarity' => $lordRule->polarity,
                'weight' => $lordRule->weight,
            ];
        }

        if ($fragments !== []) {
            $paragraphs = array_merge($paragraphs, $composer->compose($fragments, 3));
        }

        // 3. Occupants, each with their dignity/condition modifiers.
        foreach ($data['occupants'] as $name) {
            $planet = $facts['planets'][$name];
            $rule = $rules->first('planet_house', "{$name}:{$house}");

            if (! $rule) {
                continue;
            }

            $modifiers = $this->modifiersFor($planet, $rules);

            $paragraphs[] = $composer->planetSentence(
                $name,
                $this->trimText($rule->text),
                $modifiers
            );
        }

        if ($data['occupants'] === []) {
            $paragraphs[] = sprintf(
                'No graha occupies this bhava, so its affairs are read primarily through its lord %s and through '
                .'whatever aspects fall upon it. An empty house is not a weak house; it simply means the matter '
                .'is governed from elsewhere in the chart.',
                Zodiac::PLANETS_SANSKRIT[$data['lord']] ?? $data['lord']
            );
        }

        // 4. Aspects onto the house.
        $aspecting = $this->aspectsOnto($house, $facts);

        if ($aspecting !== []) {
            $paragraphs[] = $this->aspectSentence($aspecting, $house, $facts);
        }

        return [
            'key' => 'house_'.$house,
            'title' => sprintf('%s Bhava — %s', $this->ordinal($house), $meta['english']),
            'subtitle' => sprintf(
                '%s · %s significations: %s',
                $data['sign_name'],
                $meta['name'],
                $meta['significations']
            ),
            'paragraphs' => array_values(array_filter($paragraphs)),
        ];
    }

    /** Current dasha period, plainly stated. */
    private function dashaSection(array $facts, string $seed): array
    {
        $current = $facts['dasha']['current'];

        if (! $current['mahadasha']) {
            return [];
        }

        $maha = $current['mahadasha'];
        $antar = $current['antardasha'];

        $mahaPlanet = $facts['planets'][$maha['lord']] ?? null;

        $paragraphs = [];

        $paragraphs[] = sprintf(
            'You are currently running the %s Mahadasha, which began on %s and continues until %s.%s',
            Zodiac::PLANETS_SANSKRIT[$maha['lord']] ?? $maha['lord'],
            $this->formatDate($maha['start']),
            $this->formatDate($maha['end']),
            $antar ? sprintf(
                ' Within it, the %s Antardasha is active from %s to %s.',
                Zodiac::PLANETS_SANSKRIT[$antar['lord']] ?? $antar['lord'],
                $this->formatDate($antar['start']),
                $this->formatDate($antar['end'])
            ) : ''
        );

        if ($mahaPlanet) {
            $paragraphs[] = sprintf(
                'Because %s sits in your %s Bhava in %s and is %s, the themes of this period are drawn primarily '
                .'from that placement. A dasha does not introduce new material into a life; it activates what the '
                .'birth chart already contains, and the condition of the period lord determines how smoothly those '
                .'matters unfold.',
                $mahaPlanet['sanskrit'],
                $this->ordinal($mahaPlanet['house']),
                $mahaPlanet['sign_name'],
                $mahaPlanet['dignity'] === 'neutral' ? 'neutrally placed' : $mahaPlanet['dignity']
            );
        }

        $paragraphs[] = sprintf(
            'Your Vimshottari sequence began with the %s Mahadasha, of which %d years, %d months remained at birth, '
            .'calculated from the position of Chandra in %s.',
            Zodiac::PLANETS_SANSKRIT[$facts['dasha']['balance_at_birth']['lord']] ?? $facts['dasha']['balance_at_birth']['lord'],
            $facts['dasha']['balance_at_birth']['years'],
            $facts['dasha']['balance_at_birth']['months'],
            $facts['dasha']['birth_nakshatra']
        );

        return [
            'key' => 'dasha',
            'title' => 'Current Planetary Period',
            'subtitle' => sprintf(
                '%s Mahadasha%s',
                $maha['lord'],
                $antar ? ' / '.$antar['lord'].' Antardasha' : ''
            ),
            'paragraphs' => $paragraphs,
        ];
    }

    /** Collect dignity, retrogradity, combustion and digbala modifiers. */
    private function modifiersFor(array $planet, RuleRepository $rules): array
    {
        $modifiers = [];

        if ($rule = $rules->first('dignity', $planet['dignity'])) {
            // Neutral dignity adds nothing worth saying.
            if ($planet['dignity'] !== 'neutral') {
                $modifiers[] = $rule->text;
            }
        }

        if ($planet['retrograde'] && ! in_array($planet['name'], ['Rahu', 'Ketu'], true)) {
            if ($rule = $rules->first('condition', 'retrograde')) {
                $modifiers[] = $rule->text;
            }
        }

        if ($planet['combust']) {
            if ($rule = $rules->first('condition', 'combust')) {
                $modifiers[] = $rule->text;
            }
        }

        $digbala = $planet['digbala'];
        if ($digbala['applicable']) {
            $key = match (true) {
                $digbala['strength'] >= 0.84 => 'full',
                $digbala['strength'] <= 0.16 => 'powerless',
                default => null,
            };

            if ($key && $rule = $rules->first('digbala', $key)) {
                $modifiers[] = $rule->text;
            }
        }

        return $modifiers;
    }

    /** Which planets cast drishti onto this house. */
    private function aspectsOnto(int $house, array $facts): array
    {
        $aspecting = [];

        foreach ($facts['planets'] as $name => $planet) {
            if ($planet['house'] === $house) {
                continue; // occupancy is not aspect
            }

            if (in_array($house, $planet['aspects_houses'], true)) {
                $aspecting[] = $name;
            }
        }

        return $aspecting;
    }

    private function aspectSentence(array $aspecting, int $house, array $facts): string
    {
        $benefics = [];
        $malefics = [];

        foreach ($aspecting as $name) {
            $planet = $facts['planets'][$name];
            if (Zodiac::NATURE[$name] === 'benefic' && ! $planet['combust']) {
                $benefics[] = $planet['sanskrit'];
            } else {
                $malefics[] = $planet['sanskrit'];
            }
        }

        $parts = [];

        if ($benefics !== []) {
            $parts[] = sprintf(
                'The benefic drishti of %s falls here, which protects and softens the affairs of this bhava',
                $this->listify($benefics)
            );
        }

        if ($malefics !== []) {
            $parts[] = sprintf(
                '%s %s this house, adding pressure and forcing its matters to be worked for rather than received',
                $this->listify($malefics),
                count($malefics) === 1 ? 'aspects' : 'aspect'
            );
        }

        return ucfirst(implode(', while ', $parts)).'.';
    }

    /** A structural read on how the chart's weight is distributed. */
    private function chartBalance(array $facts): string
    {
        $kendra = $trikona = $dusthana = 0;

        foreach ($facts['planets'] as $planet) {
            if (in_array($planet['house'], Zodiac::KENDRA, true)) {
                $kendra++;
            }
            if (in_array($planet['house'], Zodiac::TRIKONA, true)) {
                $trikona++;
            }
            if (in_array($planet['house'], Zodiac::DUSTHANA, true)) {
                $dusthana++;
            }
        }

        $retrograde = count(array_filter(
            $facts['planets'],
            fn ($p) => $p['retrograde'] && ! in_array($p['name'], ['Rahu', 'Ketu'], true)
        ));

        $sentence = sprintf(
            'Structurally, %d of the nine grahas occupy kendras (the angular houses of action), %d occupy trikonas '
            .'(the trinal houses of fortune), and %d fall in dusthanas (the houses of difficulty and transformation).',
            $kendra, $trikona, $dusthana
        );

        if ($dusthana >= 4) {
            $sentence .= ' This is a heavily burdened distribution: a significant portion of life is spent in struggle, '
                .'illness, obligation or upheaval. Charts weighted this way tend to produce resilience that lighter '
                .'charts never develop, but the cost is real and should not be minimised.';
        } elseif ($kendra >= 4) {
            $sentence .= ' A kendra-weighted chart of this kind favours visible activity and worldly engagement; '
                .'you are unlikely to live quietly.';
        } elseif ($trikona >= 3) {
            $sentence .= ' The trinal emphasis is fortunate, indicating support that arrives without being fought for.';
        }

        if ($retrograde >= 2) {
            $sentence .= sprintf(
                ' %d planets are retrograde, which as a pattern suggests a life whose important matters resolve '
                .'later than expected and often require a second attempt.',
                $retrograde
            );
        }

        return $sentence;
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

    private function trimText(string $text): string
    {
        return rtrim(trim($text), '.');
    }

    private function formatDate(string $date): string
    {
        return Carbon::parse($date)->format('j F Y');
    }
}
