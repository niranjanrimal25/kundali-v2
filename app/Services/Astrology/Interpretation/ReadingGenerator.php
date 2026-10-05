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
    /** Houses covered by the current rule corpus — all twelve bhavas. */
    public const COVERED_HOUSES = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

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

        $sections[] = $this->yogaSection($facts, $rules);
        $sections[] = $this->afflictionSection($facts, $rules);
        $sections[] = $this->dashaSection($facts, $seed, $rules);

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

            $paragraphs[] = trim(
                $composer->planetSentence($lagnesh['name'], $lordText, $modifiers)
                .' '.$this->signSentence($lagnesh, $rules)
            );
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

        // Janma nakshatra temperament — weighted as heavily as the Lagna.
        $nakRule = $rules->first('nakshatra', (string) $moon['nakshatra']['index']);

        if ($nakRule) {
            $paragraphs[] = $this->sentence($nakRule->text);
        }

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
        $described = 0;

        foreach ($data['occupants'] as $name) {
            $planet = $facts['planets'][$name];
            $rule = $rules->first('planet_house', "{$name}:{$house}");
            $modifiers = $this->modifiersFor($planet, $rules);

            if ($rule) {
                $paragraphs[] = trim($composer->planetSentence(
                    $name,
                    $this->trimText($rule->text),
                    $modifiers
                ).' '.$this->signSentence($planet, $rules));
                $described++;

                continue;
            }

            // No rule for this combination yet. Never emit nothing —
            // state the placement factually so the section still reads
            // as a complete piece of writing.
            $paragraphs[] = $composer->planetSentence(
                $name,
                sprintf(
                    '%s occupies this bhava in %s at %s, in the nakshatra %s',
                    $planet['sanskrit'],
                    $planet['sign_name'],
                    $planet['degree_formatted'],
                    $planet['nakshatra']['name']
                ),
                $modifiers
            );
            $described++;
        }

        // Two or more grahas sharing a bhava is a third thing, not two
        // separate placements. Describe every pair present.
        $occupants = array_values($data['occupants']);

        if (count($occupants) >= 2) {
            for ($i = 0; $i < count($occupants); $i++) {
                for ($j = $i + 1; $j < count($occupants); $j++) {
                    $rule = $rules->first(
                        'conjunction',
                        RuleRepository::conjunctionKey($occupants[$i], $occupants[$j])
                    );

                    if ($rule) {
                        $paragraphs[] = $this->sentence($rule->text);
                    }
                }
            }
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
            foreach ($this->aspectSentence($aspecting, $house, $facts, $rules) as $line) {
                $paragraphs[] = $line;
            }
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
    /**
     * Yogas, doshas and the running Saturn transit.
     *
     * Every claim is stated with the factual basis the detector
     * recorded, so a reader can check the reasoning rather than take
     * the conclusion on trust. Where a dosha is cancelled, the
     * cancellation is stated rather than the dosha left standing.
     */
    private function yogaSection(array $facts, RuleRepository $rules): array
    {
        $paragraphs = [];

        $yogas = $facts['yogas'] ?? [];
        $doshas = $facts['doshas'] ?? [];
        $sadeSati = $facts['transits']['sade_sati'] ?? null;

        if ($yogas === [] && $doshas === [] && $sadeSati === null) {
            return [];
        }

        if ($yogas !== []) {
            foreach ($yogas as $yoga) {
                $rule = $rules->first('yoga', $yoga['key'].':'.$yoga['strength'])
                    ?? $rules->first('yoga', $yoga['key'].':moderate');

                if ($rule === null) {
                    continue;
                }

                $paragraphs[] = $this->sentence($rule->text).' '.$yoga['basis'];
            }
        }

        foreach ($doshas as $dosha) {
            // A cancelled dosha is reported as cancelled, not suppressed
            // and not left standing as a scare.
            $key = $dosha['cancelled'] && $dosha['key'] === 'mangal_dosha'
                ? 'mangal_dosha_mitigated'
                : $dosha['key'];

            $rule = $rules->first('dosha', $key);

            if ($rule === null) {
                continue;
            }

            // Order matters: state the finding, then the cancellation,
            // then the verdict. The verdict refers back to the
            // cancellation, so it cannot precede it.
            $parts = [$dosha['basis']];

            if ($dosha['cancellation']) {
                $parts[] = $this->sentence($dosha['cancellation']);
            }

            $parts[] = $this->sentence($rule->text);

            $paragraphs[] = trim(implode(' ', $parts));
        }

        if ($sadeSati) {
            $rule = $rules->first('transit', $sadeSati['key']);

            if ($rule) {
                // The transit fragments already restate the placement, so
                // appending the detector's basis would simply repeat it.
                $paragraphs[] = $this->sentence($rule->text);
            }
        }

        if ($paragraphs === []) {
            return [];
        }

        return [
            'key' => 'yogas',
            'title' => 'Yogas, Doshas and Transits',
            'subtitle' => sprintf(
                '%d yoga%s · %d dosha%s%s',
                count($yogas),
                count($yogas) === 1 ? '' : 's',
                count($doshas),
                count($doshas) === 1 ? '' : 's',
                $sadeSati ? ' · '.$sadeSati['name'] : ''
            ),
            'paragraphs' => $paragraphs,
        ];
    }

    /**
     * Health, relations and temperament under affliction.
     *
     * Driven by the owner-supplied corpus. Two layers:
     *   - composite rules, evaluated through ConditionMatcher
     *   - the Trik table, for a graha in the 6th, 8th or 12th
     *
     * A graha merely sitting in a dusthana is NOT enough to fire the
     * Trik table: the source assumes affliction, so we require an
     * enemy or debilitated sign, or malefic company. Applying it
     * otherwise would overstate what was supplied.
     */
    private function afflictionSection(array $facts, RuleRepository $rules): array
    {
        $matcher = new ConditionMatcher;
        $paragraphs = [];
        $cited = false;

        // 1. Compound rules.
        foreach ($rules->all('composite') as $rule) {
            $conditions = json_decode($rule->conditions ?? '[]', true) ?: [];

            if (! $matcher->matches($conditions, $facts)) {
                continue;
            }

            $paragraphs[] = $this->sentence($rule->text);
            $cited = $cited || $rule->provenance === 'classical';
        }

        // 2. The Trik table, only where the graha is genuinely afflicted.
        foreach (['Sun', 'Moon', 'Mars', 'Mercury', 'Jupiter', 'Venus', 'Saturn', 'Rahu', 'Ketu'] as $name) {
            $planet = $facts['planets'][$name] ?? null;

            if (! $planet || ! in_array($planet['house'], [6, 8, 12], true)) {
                continue;
            }

            if (! $this->isAfflicted($planet, $facts)) {
                continue;
            }

            // Each facet becomes its own sentence. Joining all three with
            // "and" produced an unreadable chain, since every facet is
            // itself a list.
            $lead = [
                'physical' => 'Physically, the indications are',
                'relations' => 'In relationships, it shows as',
                'temperament' => 'In temperament, it shows as',
            ];

            $sentences = [];

            foreach ($lead as $facet => $prefix) {
                if ($rule = $rules->first('trik_affliction', "{$name}:{$planet['house']}:{$facet}")) {
                    $sentences[] = $this->sentence($prefix.' '.$this->trimText($rule->text));
                    $cited = true;
                }
            }

            if ($sentences === []) {
                continue;
            }

            array_unshift($sentences, $this->sentence(sprintf(
                '%s stands afflicted in the %s bhava',
                $planet['sanskrit'],
                $this->ordinal($planet['house'])
            )));

            $paragraphs[] = implode(' ', $sentences);
        }

        if ($paragraphs === []) {
            return [];
        }

        // The disclaimer leads, so it is read before the content.
        array_unshift(
            $paragraphs,
            'What follows describes tendencies and vulnerabilities indicated by the chart. '
            .'It is not a medical opinion and cannot diagnose anything. Where something here '
            .'matches a symptom you actually have, treat that as a reason to see a doctor, '
            .'not as a conclusion. If any of it touches your state of mind, please speak to '
            .'someone you trust or a qualified professional.'
        );

        return [
            'key' => 'afflictions',
            'title' => 'Health, Relations and Temperament',
            'subtitle' => $cited
                ? 'Classical indications under affliction'
                : 'Indications under affliction',
            'paragraphs' => $paragraphs,
        ];
    }

    /**
     * The supplied Trik table presumes an afflicted graha, not merely
     * one placed in a dusthana.
     */
    private function isAfflicted(array $planet, array $facts): bool
    {
        if (in_array($planet['dignity'], ['debilitated', 'enemy'], true)) {
            return true;
        }

        foreach (['Saturn', 'Rahu', 'Ketu', 'Mars', 'Sun'] as $malefic) {
            $other = $facts['planets'][$malefic] ?? null;

            if ($other && $other['name'] !== $planet['name'] && $other['house'] === $planet['house']) {
                return true;
            }
        }

        return $planet['combust'] ?? false;
    }

    private function dashaSection(array $facts, string $seed, RuleRepository $rules): array
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

            // What this period actually delivers, from where its lord sits.
            $mahaRule = $rules->first(
                'dasha_lord_house',
                $maha['lord'].':'.$mahaPlanet['house']
            );

            if ($mahaRule) {
                $paragraphs[] = $this->sentence(
                    $this->dashaLeadIn($mahaRule->text).$this->trimText($mahaRule->text)
                );
            }
        }

        // The antardasha is the sub-period actually being lived right now,
        // and it modifies the mahadasha rather than replacing it.
        $antarPlanet = $antar ? ($facts['planets'][$antar['lord']] ?? null) : null;

        if ($antarPlanet) {
            $antarRule = $rules->first(
                'dasha_lord_house',
                $antar['lord'].':'.$antarPlanet['house']
            );

            if ($antarRule) {
                $paragraphs[] = $this->sentence(sprintf(
                    'Within that, the running %s Antardasha draws on its own placement in the %s Bhava: %s. '
                    .'The sub-period colours the larger one rather than overriding it, so where the two '
                    .'disagree, expect the theme to surface briefly rather than settle',
                    $antarPlanet['sanskrit'],
                    $this->ordinal($antarPlanet['house']),
                    $this->lowerFirstSafe($this->trimText($antarRule->text))
                ));
            }
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

        // Graha-in-sign already encodes dignity and is emitted as its own
        // sentence by signSentence(); fall back to the generic dignity
        // wording only where that pair is unwritten.
        $hasSignRule = $rules->first('planet_sign', $planet['name'].':'.$planet['sign']) !== null;

        if (! $hasSignRule && $planet['dignity'] !== 'neutral') {
            // Prefer the graha-specific reading of this dignity; it says
            // what the strength or weakness actually costs, rather than
            // repeating the same sentence for all nine grahas.
            $rule = $rules->first('dignity_planet', $planet['name'].':'.$planet['dignity'])
                ?? $rules->first('dignity', $planet['dignity']);

            if ($rule) {
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
            // Now four bands rather than two: the middle ground is worth
            // saying when it is the graha's own directional statement.
            $key = match (true) {
                $digbala['strength'] >= 0.84 => 'full',
                $digbala['strength'] >= 0.6 => 'strong',
                $digbala['strength'] > 0.16 => 'weak',
                default => 'powerless',
            };

            $rule = $rules->first('digbala_planet', $planet['name'].':'.$key);

            // Fall back to the generic wording, which only covers the two
            // extremes — the middle bands are not worth a generic sentence.
            if ($rule === null && in_array($key, ['full', 'powerless'], true)) {
                $rule = $rules->first('digbala', $key);
            }

            if ($rule) {
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

    private function aspectSentence(array $aspecting, int $house, array $facts, ?RuleRepository $rules = null): array
    {
        // With a rule corpus available, each graha's drishti is described
        // in its own terms rather than lumped into benefic/malefic.
        if ($rules !== null) {
            $described = [];

            foreach ($aspecting as $name) {
                // Prefer the house-specific reading of this drishti; only
                // fall back to the graha's general behaviour where the
                // specific pair is unwritten.
                $rule = $rules->first('aspect_house', $name.':'.$house)
                    ?? $rules->first('aspect', $name);

                if ($rule === null) {
                    continue;
                }

                $described[] = sprintf(
                    'the drishti of %s %s',
                    $facts['planets'][$name]['sanskrit'],
                    $this->trimText($rule->text)
                );
            }

            if ($described !== []) {
                $out = [];

                // One per sentence: these fragments contain their own full
                // stops, so joining two with "and" produces a run-on.
                foreach ($described as $one) {
                    $out[] = $this->sentence('Here '.$one);
                }

                return $out;
            }
        }

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

        return [ucfirst(implode(', while ', $parts)).'.'];
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

    /**
     * How this graha behaves in the sign it occupies, as a standalone
     * sentence. Returns null where the pair has no rule yet.
     */
    private function signSentence(array $planet, RuleRepository $rules): ?string
    {
        $rule = $rules->first('planet_sign', $planet['name'].':'.$planet['sign']);

        if ($rule === null) {
            return null;
        }

        return $this->sentence(sprintf(
            '%s is %s',
            $planet['sanskrit'],
            $this->trimText($rule->text)
        ));
    }

    /**
     * Dasha fragments are written in three shapes: noun phrases
     * ("a period of upheaval"), bare noun lists ("financial constraint
     * and family responsibility") and verb phrases ("income dominates").
     * Pick a lead-in that is grammatical for the shape at hand, so we
     * never emit "During this period, a period of upheaval".
     */
    private function dashaLeadIn(string $text): string
    {
        $text = ltrim($text);

        // "a period of upheaval" -> "This is a period of upheaval."
        if (preg_match('/^(?:a|an|the|one of|among|this)\b/i', $text)) {
            return 'This is ';
        }

        // "strongly favourable for earning" -> "The period is strongly ..."
        if (preg_match('/^(?:strongly |highly |genuinely |especially )?(?:favourable|unfavourable|excellent|difficult|demanding|auspicious)\b/i', $text)) {
            return 'The period is ';
        }

        // A bare noun list with no finite verb -> "The period brings ..."
        $firstClause = preg_split('/[.;:]/', $text)[0];

        $hasVerb = preg_match(
            '/\b(?:is|are|come|comes|dominate|dominates|rise|rises|increase|increases|'
            .'activated|indicated|bring|brings|turn|turns|arise|arises|grow|grows|'
            .'feature|features|emerge|emerges|open|opens|fall|falls|surface|surfaces|'
            .'set|sets|develop|develops|appear|appears|mark|marks|flourish|flourishes|'
            .'improve|improves|occupy|occupies|expand|expands|go|goes|run|runs|'
            .'resolve|resolves|defeated|delayed|weakened|tested|slowed|dissipate|'
            .'dissipates|mount|mounts|thin|thins|clear|clears|shift|shifts|'
            .'fluctuate|fluctuates|dissolve|dissolves|waver|wavers|warrant|warrants|'
            .'occur|occurs|enter|enters|arrive|arrives|accompany|accompanies|'
            .'can|may|will|becomes|become)\b/i',
            $firstClause
        );

        return $hasVerb ? 'During this period, ' : 'The period brings ';
    }

    /** Lowercase a fragment's opening word, leaving proper nouns alone. */
    private function lowerFirstSafe(string $text): string
    {
        if (preg_match('/^(?:[A-Z][a-z]+\s)?[A-Z]/', $text)) {
            return $text;
        }

        return mb_strtolower(mb_substr($text, 0, 1)).mb_substr($text, 1);
    }

    /** Capitalise and terminate a rule fragment used as a whole sentence. */
    private function sentence(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if ($text === '') {
            return '';
        }

        $text = mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text, 1);

        return rtrim($text, '.').'.';
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
