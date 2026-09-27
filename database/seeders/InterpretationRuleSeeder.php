<?php

namespace Database\Seeders;

use Database\Seeders\Rules\AspectHouseRules;
use Database\Seeders\Rules\AspectRules;
use Database\Seeders\Rules\ConjunctionRules;
use Database\Seeders\Rules\CoreRules;
use Database\Seeders\Rules\DashaRules;
use Database\Seeders\Rules\DigbalaRules;
use Database\Seeders\Rules\DignityRules;
use Database\Seeders\Rules\HouseSignRules;
use Database\Seeders\Rules\LordPlacementRules;
use Database\Seeders\Rules\NakshatraRules;
use Database\Seeders\Rules\PlanetHouseRules;
use Database\Seeders\Rules\PlanetSignRules;
use Database\Seeders\Rules\YogaRules;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The English interpretation corpus, covering all twelve bhavas.
 *
 * Provenance: these fragments are a modern synthesis written in the
 * Parashari idiom. The computational layer the rules sit on — exaltation
 * degrees, moolatrikona, drishti, digbala and the Vimshottari sequence —
 * is classical. The prose itself is NOT a translation or quotation of
 * Brihat Parashara Hora Shastra or any other source text.
 *
 * Each row is ONE assertion. The composer selects matching rows, resolves
 * contradictions by weight and polarity, then stitches them into flowing
 * paragraphs. Fragments are therefore written as sentence-level clauses
 * that read naturally when joined — not as standalone bullet points.
 *
 * polarity: -2 severe · -1 difficult · 0 neutral · +1 favourable · +2 strong
 * weight:   higher wins when two fragments contradict
 *
 * Composition:
 *   CoreRules           12 lagna_sign + 13 modifiers  =  25
 *   HouseSignRules      12 houses x 12 signs          = 144
 *   PlanetHouseRules    9 grahas x 12 houses          = 108
 *   LordPlacementRules  12 lords x 12 houses          = 144
 *                                                 total 421
 */
class InterpretationRuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('interpretation_rules')->where('locale', 'en')->delete();

        $rows = array_merge(
            CoreRules::all(),
            HouseSignRules::all(),
            PlanetHouseRules::all(),
            LordPlacementRules::all(),
            PlanetSignRules::all(),
            ConjunctionRules::all(),
            AspectRules::all(),
            AspectHouseRules::all(),
            NakshatraRules::all(),
            DashaRules::all(),
            DignityRules::all(),
            DigbalaRules::all(),
            YogaRules::all(),
        );

        $rows = array_map(fn ($r) => $this->clean($r), $rows);

        $now = now();
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('interpretation_rules')->insert(array_map(
                fn ($r) => $r + ['locale' => 'en', 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }

        $this->command?->info('Seeded '.count($rows).' interpretation rules.');
    }

    /**
     * Prose hygiene applied uniformly, so no individual fragment can
     * reintroduce a bug we have already fixed once.
     */
    private function clean(array $row): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $row['text']));

        // A fragment must never open with a conjunction: it sometimes leads
        // a sentence, and "And placed in the 5th..." reads as broken.
        $text = preg_replace('/^(?:and|but|so|yet|which)\s+/i', '', $text);

        // Ampersands never belong in running prose.
        $text = str_replace(' & ', ' and ', $text);

        // Fragments are clauses; the composer supplies terminal punctuation.
        $text = rtrim($text, '.');

        $row['text'] = $text;

        return $row;
    }
}
