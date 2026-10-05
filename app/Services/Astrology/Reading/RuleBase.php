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

    /** @return list<array> every evaluable rule */
    public function rules(): array
    {
        return Cache::store('array')->rememberForever('rulebase.rules', function () {
            $rules = [];

            foreach (['composite', 'trik'] as $file) {
                foreach ($this->load($file) as $rule) {
                    $rules[] = $rule;
                }
            }

            return $rules;
        });
    }

    /** Karakatwa is reference data, not an evaluable rule. */
    public function karakatwa(): array
    {
        return $this->load('karakatwa');
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
