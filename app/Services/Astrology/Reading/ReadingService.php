<?php

namespace App\Services\Astrology\Reading;

use App\Models\Kundali;
use App\Models\Reading;
use App\Services\Astrology\KundaliService;

/**
 * Orchestrates the four-stage pipeline:
 *
 *   birth data -> ephemeris -> rule evaluation -> report
 *
 * Stages 1 and 2 are the existing, unchanged chart engine. This class
 * owns stages 3 and 4.
 */
class ReadingService
{
    public function __construct(
        private readonly KundaliService $charts,
        private readonly RuleBase $ruleBase,
        private readonly RuleEngine $engine,
        private readonly IntersectionEngine $intersection,
        private readonly LagneshAnalyser $lagnesh,
        private readonly ReportAggregator $aggregator,
    ) {}

    /** Build (or reuse) the report for a saved kundali. */
    public function forKundali(Kundali $kundali, string $locale = 'en', bool $force = false): array
    {
        $existing = $kundali->reading($locale);
        $fingerprint = $this->fingerprint().':'.$locale;

        if (! $force && $existing !== null && $existing->corpus_fingerprint === $fingerprint) {
            return $existing->sections;
        }

        $report = $this->generate($this->charts->facts($kundali), $locale);

        Reading::updateOrCreate(
            ['kundali_id' => $kundali->id, 'locale' => $locale],
            ['sections' => $report, 'corpus_fingerprint' => $fingerprint],
        );

        $kundali->unsetRelation('readings');

        return $report;
    }

    /** Run the pipeline against an already-computed chart. */
    public function generate(array $facts, string $locale = 'en'): array
    {
        $payload = ChartPayload::fromFacts($facts);

        $explicit = $this->engine->evaluate($this->ruleBase->rules($locale), $payload);

        // Filtered intersection, not a Cartesian product: only the
        // attributes the bhava actually governs survive.
        $blocks = $this->intersection->forHouses($payload);

        // The Lagna and its lord are established before anything else,
        // because the rest of the reading is read against them.
        $lagnesh = $this->lagnesh->analyse($payload);

        return $this->aggregator->build($payload, $explicit, $locale, $blocks, $lagnesh);
    }

    /**
     * Changes to the rule base invalidate stored reports, so a reading
     * is never served from a corpus that no longer exists.
     */
    public function fingerprint(): string
    {
        $parts = [];

        foreach ($this->ruleBase->files() as $file) {
            $path = base_path(RuleBase::PATH."/{$file}.json");
            $parts[] = $file.':'.(is_file($path) ? md5_file($path) : '');
        }

        // Translation files are part of the corpus too, so editing one
        // rebuilds stored reports in that locale.
        foreach (glob(base_path(RuleBase::PATH.'/*/*.json')) ?: [] as $path) {
            $parts[] = basename(dirname($path)).'/'.basename($path).':'.md5_file($path);
        }

        return substr(hash('sha256', implode('|', $parts)), 0, 32);
    }
}
