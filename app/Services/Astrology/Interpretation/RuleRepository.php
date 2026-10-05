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

    /** Provenance values this instance is allowed to use. */
    private array $allowed;

    public function __construct(
        private readonly string $locale = 'en',
        ?array $provenance = null,
    ) {
        $this->allowed = $provenance ?? self::activeProvenance();
    }

    /**
     * Which rule sources are switched on, per config/jyotish.php.
     *
     * Rules outside the active set stay in the database but are never
     * read, so switching back is a config change rather than a reseed.
     */
    public static function activeProvenance(): array
    {
        $mode = config('jyotish.rule_sources', 'all');

        if (is_array($mode)) {
            return $mode;
        }

        $modes = config('jyotish.rule_source_modes', []);

        return $modes[$mode] ?? $modes['all'] ?? ['classical', 'traditional-consensus', 'modern-synthesis'];
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $rules = DB::table('interpretation_rules')
            ->where('locale', $this->locale)
            ->whereIn('provenance', $this->allowed)
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

    /**
     * Canonical, order-independent key for a graha pair, so a lookup
     * never depends on which graha the caller reached first.
     */
    public static function conjunctionKey(string $a, string $b): string
    {
        $pair = [$a, $b];
        sort($pair);

        return implode('+', $pair);
    }

    /** The single strongest fragment for a condition, or null. */
    public function first(string $type, string $key): ?object
    {
        return $this->find($type, $key)[0] ?? null;
    }

    /** Every rule of a given type, strongest first. */
    public function all(string $type): array
    {
        $this->load();

        $out = [];

        foreach ($this->index as $key => $rules) {
            if (str_starts_with($key, $type.'|')) {
                foreach ($rules as $rule) {
                    $out[] = $rule;
                }
            }
        }

        return $out;
    }

    /**
     * Identifies the corpus a reading was built from.
     *
     * Combines the active rule sources, the number of visible rules and
     * the latest rule update. Any of those changing means a stored
     * reading is out of date, which is what lets the cache invalidate
     * itself rather than needing a manual truncate after every reseed.
     */
    public static function fingerprint(string $locale = 'en'): string
    {
        $allowed = self::activeProvenance();

        $row = DB::table('interpretation_rules')
            ->where('locale', $locale)
            ->whereIn('provenance', $allowed)
            ->selectRaw('count(*) as c, max(updated_at) as m')
            ->first();

        return substr(hash('sha256', implode('|', [
            implode(',', $allowed),
            $locale,
            $row->c ?? 0,
            $row->m ?? '',
        ])), 0, 32);
    }

    public function count(): int
    {
        $this->load();

        return array_sum(array_map('count', $this->index));
    }
}
