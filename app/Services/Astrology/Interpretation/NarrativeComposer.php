<?php

namespace App\Services\Astrology\Interpretation;

/**
 * Turns a set of matched rule fragments into flowing prose.
 *
 * This is what separates a real reading from a bullet list. Three jobs:
 *
 *  1. CONFLICT RESOLUTION — when consecutive fragments disagree in
 *     polarity, join them concessively ("Yet...", "That said...") instead
 *     of printing two contradictory statements side by side.
 *
 *  2. EMPHASIS BY STRENGTH — strongly-weighted fragments get emphatic
 *     connectors; weak ones get hedged ones.
 *
 *  3. VARIATION — connectors are chosen from pools using a deterministic
 *     seed derived from the chart, so two different charts read
 *     differently while the same chart always reads identically.
 */
class NarrativeComposer
{
    /** Joins two fragments of the SAME polarity direction. */
    private const ADDITIVE = [
        'Moreover, ', 'In addition, ', 'Alongside this, ', 'Further, ', 'Equally, ',
    ];

    /** Joins fragments where polarity FLIPS — the concessive case. */
    private const CONCESSIVE = [
        'That said, ', 'Yet ', 'Even so, ', 'At the same time, ', 'Set against this, ',
        'Working in the other direction, ',
    ];

    /** Opens a consequence drawn from a preceding statement. */
    private const CONSEQUENTIAL = [
        'As a result, ', 'Consequently, ', 'The effect of this is that ',
    ];

    private int $seed;

    private int $counter = 0;

    public function __construct(string $seedSource = '')
    {
        // Deterministic per chart: same birth data always reads the same.
        $this->seed = crc32($seedSource);
    }

    /**
     * Compose fragments into one or more paragraphs.
     *
     * @param  array<int, array{text: string, polarity: int, weight: int}>  $fragments
     */
    public function compose(array $fragments, int $sentencesPerParagraph = 4): array
    {
        $fragments = array_values(array_filter(
            $fragments,
            fn ($f) => trim($f['text'] ?? '') !== ''
        ));

        if ($fragments === []) {
            return [];
        }

        $sentences = [];
        $previousPolarity = null;

        foreach ($fragments as $i => $fragment) {
            $text = $this->normalise($fragment['text']);
            $polarity = $fragment['polarity'] ?? 0;

            if ($i === 0) {
                $sentences[] = $this->sentence($text);
                $previousPolarity = $polarity;

                continue;
            }

            $connector = $this->connectorFor($previousPolarity, $polarity);

            $sentences[] = $this->sentence($connector.$this->lowerFirst($text));

            $previousPolarity = $polarity;
        }

        return array_map(
            fn ($chunk) => implode(' ', $chunk),
            array_chunk($sentences, max(1, $sentencesPerParagraph))
        );
    }

    /**
     * Build a sentence about a planet, assembling its base statement with
     * any dignity, retrogradity or digbala modifiers.
     *
     * Modifiers are capped at two per sentence — chaining three or more
     * with commas produces the run-on prose that makes generated readings
     * sound machine-written.
     */
    public function planetSentence(string $planetName, string $base, array $modifiers): string
    {
        $base = $this->normalise($base);

        if ($modifiers === []) {
            return $this->sentence($base);
        }

        $out = [$this->sentence($base)];

        foreach (array_chunk($modifiers, 2) as $chunk) {
            $out[] = $this->sentence('It '.$this->joinModifiers($chunk));
        }

        return implode(' ', $out);
    }

    private function joinModifiers(array $modifiers): string
    {
        $modifiers = array_map(fn ($m) => $this->lowerFirst($this->normalise($m)), $modifiers);

        if (count($modifiers) === 1) {
            return $modifiers[0];
        }

        $last = array_pop($modifiers);

        return implode(', ', $modifiers).', and '.$last;
    }

    /**
     * Choose a connector based on how the polarity moved.
     * A flip from positive to negative (or vice versa) must read
     * concessively or the paragraph contradicts itself.
     */
    private function connectorFor(?int $previous, int $current): string
    {
        $previous ??= 0;

        $flipped = ($previous > 0 && $current < 0) || ($previous < 0 && $current > 0);

        // A neutral statement followed by a difficult one also needs a
        // concessive turn — "Equally, ... you must guard against chronic
        // depletion" reads as a non-sequitur.
        $turningDifficult = $previous >= 0 && $current < 0;

        if ($flipped || $turningDifficult) {
            return $this->pick(self::CONCESSIVE);
        }

        // Strong reinforcement of an existing direction reads as a consequence.
        if ($previous !== 0 && $current !== 0 && abs($current) >= 2) {
            return $this->pick(self::CONSEQUENTIAL);
        }

        return $this->pick(self::ADDITIVE);
    }

    /** Deterministic pseudo-random choice, stable for a given chart. */
    private function pick(array $pool): string
    {
        $index = ($this->seed + ($this->counter++ * 2654435761)) % count($pool);

        return $pool[abs($index)];
    }

    private function normalise(string $text): string
    {
        return trim(rtrim(trim($text), '.'));
    }

    private function sentence(string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        $text = mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text, 1);

        return rtrim($text, '.').'.';
    }

    private function lowerFirst(string $text): string
    {
        // Never lowercase a proper noun (planet or sign name).
        if (preg_match('/^(Surya|Chandra|Mangal|Budha|Guru|Shukra|Shani|Rahu|Ketu|The|You|Your|Sun|Moon|Mars|Mercury|Jupiter|Venus|Saturn)\b/', $text)) {
            return $text;
        }

        return mb_strtolower(mb_substr($text, 0, 1)).mb_substr($text, 1);
    }
}
