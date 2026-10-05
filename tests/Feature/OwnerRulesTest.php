<?php

namespace Tests\Feature;

use App\Models\Kundali;
use App\Models\User;
use App\Services\Astrology\Interpretation\ConditionMatcher;
use App\Services\Astrology\Interpretation\ReadingGenerator;
use App\Services\Astrology\KundaliService;
use Database\Seeders\InterpretationRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OwnerRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InterpretationRuleSeeder::class);
    }

    private function kundali(): Kundali
    {
        return Kundali::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Demo Chart',
            'birth_date' => '1990-05-15',
            'birth_time' => '10:30:00',
            'birth_place' => 'Pokhara, Nepal',
            'latitude' => 28.26689,
            'longitude' => 83.96851,
            'timezone' => 'Asia/Kathmandu',
        ]);
    }

    #[Test]
    public function owner_rules_are_recorded_as_classical_with_a_source(): void
    {
        $owner = DB::table('interpretation_rules')->where('provenance', 'classical')->get();

        $this->assertCount(112, $owner, 'The supplied corpus should be 112 rules');

        foreach ($owner as $rule) {
            $this->assertNotNull($rule->source, "{$rule->condition_key} has no source");
        }
    }

    #[Test]
    public function the_trik_table_covers_nine_grahas_three_houses_three_facets(): void
    {
        $count = DB::table('interpretation_rules')
            ->where('condition_type', 'trik_affliction')
            ->count();

        $this->assertSame(81, $count, '9 grahas x 3 houses x 3 facets');
    }

    #[Test]
    public function the_condition_matcher_requires_every_key_to_match(): void
    {
        $facts = app(KundaliService::class)->facts($this->kundali(), true);
        $matcher = new ConditionMatcher;

        // Saturn is genuinely in the 7th with Rahu in this chart.
        $this->assertTrue($matcher->matches(
            ['planet' => 'Saturn', 'in_house' => [7], 'with_any' => ['Rahu']],
            $facts
        ));

        // Same rule, one clause wrong, must not fire.
        $this->assertFalse($matcher->matches(
            ['planet' => 'Saturn', 'in_house' => [7], 'with_any' => ['Jupiter']],
            $facts
        ));

        $this->assertFalse($matcher->matches(
            ['planet' => 'Saturn', 'in_house' => [3], 'with_any' => ['Rahu']],
            $facts
        ));
    }

    #[Test]
    public function lordship_is_computed_from_the_ascendant(): void
    {
        $matcher = new ConditionMatcher;

        // Cancer ascendant: Moon rules the 1st, Saturn the 7th and 8th.
        $this->assertSame([1], $matcher->housesRuledBy('Moon', 3));
        $this->assertSame([7, 8], $matcher->housesRuledBy('Saturn', 3));
    }

    #[Test]
    public function an_unafflicted_graha_in_a_dusthana_does_not_trigger_the_trik_table(): void
    {
        $facts = app(KundaliService::class)->facts($this->kundali(), true);

        // Venus is exalted in the 9th — not a dusthana, so it must be absent.
        $sections = app(ReadingGenerator::class)->generate($facts);
        $affliction = collect($sections)->firstWhere('key', 'afflictions');

        $this->assertNotNull($affliction);

        $text = implode(' ', $affliction['paragraphs']);
        $this->assertStringNotContainsString('Shukra stands afflicted', $text);
    }

    #[Test]
    public function the_affliction_section_leads_with_a_health_disclaimer(): void
    {
        $sections = app(ReadingGenerator::class)->forKundali($this->kundali(), 'en', true);
        $affliction = collect($sections)->firstWhere('key', 'afflictions');

        $this->assertNotNull($affliction, 'The chart should produce an affliction section');

        $first = $affliction['paragraphs'][0];

        $this->assertStringContainsString('not a medical opinion', $first);
        $this->assertStringContainsString('cannot diagnose', $first);
    }

    #[Test]
    public function composite_rules_actually_fire_on_a_real_chart(): void
    {
        $sections = app(ReadingGenerator::class)->forKundali($this->kundali(), 'en', true);
        $affliction = collect($sections)->firstWhere('key', 'afflictions');

        // Disclaimer plus at least one real finding.
        $this->assertGreaterThan(1, count($affliction['paragraphs']));
    }

    #[Test]
    public function every_composite_rule_has_valid_parseable_conditions(): void
    {
        $matcher = new ConditionMatcher;

        $rules = DB::table('interpretation_rules')
            ->whereIn('condition_type', ['composite', 'trik_affliction'])
            ->get();

        foreach ($rules as $rule) {
            $decoded = json_decode($rule->conditions, true);

            $this->assertIsArray($decoded, "{$rule->condition_key}: conditions is not valid JSON");
            $this->assertNotEmpty($decoded, "{$rule->condition_key}: empty conditions can never match");
            $this->assertSame([], $matcher->unknownKeys($decoded),
                "{$rule->condition_key}: uses condition keys the matcher does not understand");
        }
    }
}
