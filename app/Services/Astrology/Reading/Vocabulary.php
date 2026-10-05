<?php

namespace App\Services\Astrology\Reading;

/**
 * Translates chart terminology that originates in the engine rather
 * than the rule base: graha names, rashi names, bhava ordinals and
 * bhava significations.
 *
 * Every lookup falls back to the English term, so a missing entry
 * degrades one word rather than breaking a sentence.
 */
class Vocabulary
{
    public function __construct(private readonly string $locale = 'en') {}

    private function map(string $key): array
    {
        if ($this->locale === 'en') {
            return [];
        }

        return (array) trans("jyotish.{$key}", [], $this->locale);
    }

    public function graha(string $name, string $fallback): string
    {
        return $this->map('grahas')[$name] ?? $fallback;
    }

    public function sign(string $name): string
    {
        return $this->map('signs')[$name] ?? $name;
    }

    /** "4th" in English, "चतुर्थ" in Nepali. */
    public function ordinal(int $n): string
    {
        if ($this->locale === 'en') {
            $suffix = match (true) {
                in_array($n % 100, [11, 12, 13], true) => 'th',
                $n % 10 === 1 => 'st',
                $n % 10 === 2 => 'nd',
                $n % 10 === 3 => 'rd',
                default => 'th',
            };

            return $n.$suffix;
        }

        return $this->map('ordinals')[$n] ?? (string) $n;
    }

    public function houseLabel(int $house, string $fallback): string
    {
        return $this->map('houseLabels')[$house] ?? $fallback;
    }

    public function significations(int $house, string $fallback): string
    {
        return $this->map('significations')[$house] ?? $fallback;
    }

    public function houseWord(): string
    {
        return $this->locale === 'en' ? 'House' : (trans('jyotish.house', [], $this->locale) ?: 'भाव');
    }
}
