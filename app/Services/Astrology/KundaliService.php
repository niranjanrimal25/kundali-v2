<?php

namespace App\Services\Astrology;

use App\Models\ChartData;
use App\Models\Kundali;

/**
 * Application-facing orchestrator.
 *
 * Owns the caching policy: a chart is deterministic for a given birth
 * record, so it is computed once and reused until the birth data or the
 * engine version changes.
 */
class KundaliService
{
    public function __construct(
        private readonly ChartCalculator $calculator,
    ) {}

    /**
     * Return the ChartFacts for a Kundali, computing and caching if needed.
     */
    public function facts(Kundali $kundali, bool $force = false): array
    {
        $cached = $kundali->chartData;

        if (! $force
            && $cached !== null
            && $cached->engine_version === ChartCalculator::ENGINE_VERSION) {
            return $cached->facts;
        }

        $facts = $this->calculator->forKundali($kundali);

        ChartData::updateOrCreate(
            ['kundali_id' => $kundali->id],
            [
                'facts' => $facts,
                'julian_day' => $facts['meta']['julian_day'],
                'ayanamsa' => $facts['meta']['ayanamsa_value'],
                'lagna_sign' => $facts['lagna']['sign'],
                'moon_sign' => $facts['moon']['sign'],
                'moon_nakshatra' => $facts['moon']['nakshatra']['index'],
                'engine_version' => ChartCalculator::ENGINE_VERSION,
            ]
        );

        // Keep the resolved offset on the birth record for display.
        $kundali->forceFill([
            'utc_offset_hours' => $facts['meta']['utc_offset_hours'],
        ])->saveQuietly();

        $kundali->unsetRelation('chartData');

        return $facts;
    }
}
