<?php

namespace App\Services\Astrology\Reading;

use Illuminate\Support\Facades\Cache;

/**
 * STEP 2 — the rule repository.
 *
 * JSON files under resources/rules are the source of truth. They are
 * loaded once per request and cached, so adding a rule means editing a
 * file, not writing code or running a migration.
 */
class RuleBase
{
    public const PATH = 'resources/rules';

    /** @return list<array> every evaluable rule, in the given locale */
    public function rules(string $locale = 'en'): array
    {
        return Cache::store('array')->rememberForever("rulebase.rules.{$locale}", function () use ($locale) {
            $rules = [];

            foreach (['composite', 'trik'] as $file) {
                foreach ($this->localised($file, $locale) as $rule) {
                    $rules[] = $rule;
                }
            }

            return $rules;
        });
    }

    /** Karakatwa is reference data, not an evaluable rule. */
    public function karakatwa(string $locale = 'en'): array
    {
        return $this->localised('karakatwa', $locale);
    }

    /**
     * English is the source of truth for CONDITIONS; a translation file
     * only overlays the output text. That way a translator can never
     * accidentally change which charts a rule fires on, and an
     * untranslated rule degrades to English rather than disappearing.
     */
    public function localised(string $file, string $locale): array
    {
        $rules = $this->load($file);

        if ($locale === 'en') {
            return $rules;
        }

        $overlay = [];

        foreach ($this->load("{$locale}/{$file}") as $row) {
            if (isset($row['id'])) {
                $overlay[$row['id']] = $row;
            }
        }

        foreach ($rules as &$rule) {
            $translation = $overlay[$rule['id']] ?? null;

            if ($translation === null) {
                continue;
            }

            // Only text is translatable. Everything else is structure.
            foreach (['text', 'lead'] as $key) {
                if (isset($translation[$key])) {
                    $rule[$key] = $translation[$key];
                }
            }

            if (isset($translation['then']['text'])) {
                $rule['then']['text'] = $translation['then']['text'];
            }

            if (isset($translation['then']['title'])) {
                $rule['then']['title'] = $translation['then']['title'];
            }

            $rule['translated'] = true;
        }

        return $rules;
    }

    /** Rules with no translation in the given locale. */
    public function untranslated(string $locale): array
    {
        $missing = [];

        foreach (['composite', 'trik', 'karakatwa'] as $file) {
            foreach ($this->localised($file, $locale) as $rule) {
                if (! ($rule['translated'] ?? false)) {
                    $missing[] = ['file' => $file, 'id' => $rule['id']];
                }
            }
        }

        return $missing;
    }

    public function load(string $file): array
    {
        $path = base_path(self::PATH."/{$file}.json");

        if (! is_file($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?: [];
    }

    /** Every file in the rule base, for the audit command. */
    public function files(): array
    {
        return array_map(
            fn ($p) => basename($p, '.json'),
            glob(base_path(self::PATH.'/*.json')) ?: []
        );
    }
}
