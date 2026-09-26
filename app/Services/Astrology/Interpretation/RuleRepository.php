<?php

namespace App\Services\Astrology\Interpretation;

use Illuminate\Support\Facades\DB;

/**
 * Loads the interpretation corpus once per reading and serves lookups
 * from memory, so composing a report is a single query rather than a
 * few hundred.
 */
class RuleRepository
{
    /** @var array<string, array<int, object>> keyed "type|key" */
    private array $index = [];

    private bool $loaded = false;

    public function __construct(private readonly string $locale = 'en') {}

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $rules = DB::table('interpretation_rules')
            ->where('locale', $this->locale)
            ->orderByDesc('weight')
            ->get();

        foreach ($rules as $rule) {
            $this->index[$rule->condition_type.'|'.$rule->condition_key][] = $rule;
        }

        $this->loaded = true;
    }

    /** All fragments matching a condition, strongest first. */
    public function find(string $type, string $key): array
    {
        $this->load();

        return $this->index[$type.'|'.$key] ?? [];
    }

    /** The single strongest fragment for a condition, or null. */
    public function first(string $type, string $key): ?object
    {
        return $this->find($type, $key)[0] ?? null;
    }

    public function count(): int
    {
        $this->load();

        return array_sum(array_map('count', $this->index));
    }
}
