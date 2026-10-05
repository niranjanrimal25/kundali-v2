<?php

namespace Tests\Feature;

use App\Models\Kundali;
use App\Models\User;
use App\Services\Astrology\KundaliService;
use App\Services\Astrology\Reading\ChartPayload;
use App\Services\Astrology\Reading\ReadingService;
use App\Services\Astrology\Reading\RuleBase;
use App\Services\Astrology\Reading\RuleEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RuleEngineTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        $kundali = Kundali::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Demo Chart',
            'birth_date' => '1990-05-15',
            'birth_time' => '10:30:00',
            'birth_place' => 'Pokhara, Nepal',
            'latitude' => 28.26689,
            'longitude' => 83.96851,
            'timezone' => 'Asia/Kathmandu',
        ]);

        return ChartPayload::fromFacts(app(KundaliService::class)->facts($kundali, true));
    }

    #[Test]
    public function the_payload_exposes_the_documented_facts(): void
    {
        $p = $this->payload();

        // Cancer ascendant.
        $this->assertSame(3, $p['lagna']['sign']);
        $this->assertSame(4, $p['lagna']['signNumber']);

        // Saturn: 7th house, own sign, retrograde, with Rahu.
        $this->assertSame(7, $p['planet']['Saturn']['house']);
        $this->assertSame('own', $p['planet']['Saturn']['dignity']);
        $this->assertTrue($p['planet']['Saturn']['retrograde']);
        $this->assertContains('Rahu', $p['planet']['Saturn']['conjunctWith']);

        // lord.N resolves without naming a graha.
        $this->assertSame('Moon', $p['lord'][1]['name']);
        $this->assertSame(6, $p['lord'][1]['house']);

        // sign.N tells you where a rashi fell.
        $this->assertSame(1, $p['sign'][3]['house']);
    }

    #[Test]
    public function influenced_by_covers_conjunction_and_aspect(): void
    {
        $p = $this->payload();

        $saturn = $p['planet']['Saturn'];

        foreach ($saturn['conjunctWith'] as $g) {
            $this->assertContains($g, $saturn['influencedBy']);
        }

        foreach ($saturn['aspectedBy'] as $g) {
            $this->assertContains($g, $saturn['influencedBy']);
        }
    }

    #[Test]
    public function every_operator_behaves_as_documented(): void
    {
        $engine = new RuleEngine;
        $p = $this->payload();

        $cases = [
            [['fact' => 'planet.Saturn.house', 'equals' => 7], true],
            [['fact' => 'planet.Saturn.house', 'equals' => 3], false],
            [['fact' => 'planet.Saturn.house', 'in' => [6, 7, 8]], true],
            [['fact' => 'planet.Saturn.house', 'notIn' => [6, 7, 8]], false],
            [['fact' => 'planet.Saturn.conjunctWith', 'includesAny' => ['Rahu']], true],
            [['fact' => 'planet.Saturn.conjunctWith', 'includesAny' => ['Jupiter']], false],
            [['fact' => 'planet.Saturn.conjunctWith', 'includesNone' => ['Jupiter']], true],
            [['fact' => 'planet.Saturn.retrograde', 'isTrue' => true], true],
            [['fact' => 'planet.Saturn.combust', 'isTrue' => true], false],
            [['fact' => 'planet.Saturn.lordOf', 'includesAll' => [7, 8]], true],
            // An unknown fact path must fail closed, never match.
            [['fact' => 'planet.Nonexistent.house', 'equals' => 7], false],
            // An unknown operator must fail closed too.
            [['fact' => 'planet.Saturn.house', 'wobble' => 7], false],
        ];

        foreach ($cases as $i => [$condition, $expected]) {
            $this->assertSame($expected, $engine->test($condition, $p), "case {$i}");
        }
    }

    #[Test]
    public function a_rule_with_no_conditions_never_fires(): void
    {
        $engine = new RuleEngine;

        $this->assertFalse($engine->matches([], $this->payload()));
        $this->assertFalse($engine->matches(['all' => []], $this->payload()));
    }

    #[Test]
    public function every_rule_in_the_base_is_valid(): void
    {
        $engine = new RuleEngine;
        $base = new RuleBase;

        $rules = $base->rules();

        $this->assertGreaterThan(100, count($rules), 'The rule base should not be empty');

        $ids = [];

        foreach ($rules as $rule) {
            $problems = $engine->validate($rule);

            $this->assertSame([], $problems,
                ($rule['id'] ?? '?').': '.implode('; ', $problems));

            $this->assertArrayNotHasKey($rule['id'], $ids, "duplicate rule id {$rule['id']}");
            $ids[$rule['id']] = true;
        }
    }

    #[Test]
    public function the_report_has_the_three_required_sections(): void
    {
        $kundali = Kundali::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Demo Chart',
            'birth_date' => '1990-05-15',
            'birth_time' => '10:30:00',
            'birth_place' => 'Pokhara, Nepal',
            'latitude' => 28.26689,
            'longitude' => 83.96851,
            'timezone' => 'Asia/Kathmandu',
        ]);

        $report = app(ReadingService::class)->forKundali($kundali, 'en', true);

        $this->assertNotEmpty($report['placements']);
        $this->assertNotEmpty($report['groups']);
        $this->assertNotEmpty($report['summary']);

        $md = $report['markdown'];
        $this->assertStringContainsString('## 1. Chart Placement Overview', $md);
        $this->assertStringContainsString('## 2. Detailed Analysis Based On Your Rules', $md);
        $this->assertStringContainsString('## 3. Summary of Key Outcomes', $md);
    }

    #[Test]
    public function only_occupied_houses_are_listed(): void
    {
        $p = $this->payload();
        $report = app(ReadingService::class)->generate(
            app(KundaliService::class)->facts(Kundali::first() ?? Kundali::create([
                'user_id' => User::factory()->create()->id,
                'name' => 'X', 'birth_date' => '1990-05-15', 'birth_time' => '10:30:00',
                'birth_place' => 'Pokhara', 'latitude' => 28.26689, 'longitude' => 83.96851,
                'timezone' => 'Asia/Kathmandu',
            ]), true)
        );

        foreach ($report['placements'] as $placement) {
            $this->assertNotEmpty(
                $p['house'][$placement['house']]['occupants'],
                "House {$placement['house']} is listed but empty"
            );
        }
    }

    #[Test]
    public function explicit_and_derived_findings_are_separated_for_display(): void
    {
        $kundali = Kundali::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Demo Chart',
            'birth_date' => '1990-05-15',
            'birth_time' => '10:30:00',
            'birth_place' => 'Pokhara, Nepal',
            'latitude' => 28.26689,
            'longitude' => 83.96851,
            'timezone' => 'Asia/Kathmandu',
        ]);

        $report = app(ReadingService::class)->forKundali($kundali, 'en', true);

        $sawDerived = false;

        foreach ($report['groups'] as $group) {
            $this->assertArrayHasKey('explicit', $group);
            $this->assertArrayHasKey('derived', $group);

            foreach ($group['explicit'] as $point) {
                $this->assertFalse($point['derived'], 'A derived point leaked into the explicit list');
            }

            foreach ($group['derived'] as $point) {
                $this->assertTrue($point['derived']);
                $sawDerived = true;
            }

            $this->assertCount(
                count($group['points']),
                array_merge($group['explicit'], $group['derived']),
                'Splitting must not lose or duplicate a point'
            );
        }

        $this->assertTrue($sawDerived, 'This chart should produce derived points');
    }

    #[Test]
    public function the_summary_reports_only_what_the_rule_base_matched(): void
    {
        $kundali = Kundali::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Demo Chart',
            'birth_date' => '1990-05-15',
            'birth_time' => '10:30:00',
            'birth_place' => 'Pokhara, Nepal',
            'latitude' => 28.26689,
            'longitude' => 83.96851,
            'timezone' => 'Asia/Kathmandu',
        ]);

        $report = app(ReadingService::class)->forKundali($kundali, 'en', true);

        // Collect every derived sentence, then assert none reached the
        // summary - derived material would otherwise swamp it.
        $derivedText = [];

        foreach ($report['groups'] as $group) {
            foreach ($group['derived'] as $point) {
                $derivedText[] = $point['text'];
            }
        }

        foreach ($report['summary'] as $bucket) {
            foreach ($bucket['points'] as $point) {
                $this->assertNotContains($point, $derivedText,
                    'A derived point appeared in the summary');
            }
        }
    }

    #[Test]
    public function derived_points_are_marked_and_rank_below_explicit_rules(): void
    {
        $kundali = Kundali::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Demo Chart',
            'birth_date' => '1990-05-15',
            'birth_time' => '10:30:00',
            'birth_place' => 'Pokhara, Nepal',
            'latitude' => 28.26689,
            'longitude' => 83.96851,
            'timezone' => 'Asia/Kathmandu',
        ]);

        $report = app(ReadingService::class)->forKundali($kundali, 'en', true);

        $this->assertGreaterThan(0, $report['stats']['explicit']);
        $this->assertGreaterThan(0, $report['stats']['derived']);

        foreach ($report['groups'] as $group) {
            $seenDerived = false;

            foreach ($group['points'] as $point) {
                if ($point['derived']) {
                    $seenDerived = true;

                    continue;
                }

                $this->assertFalse(
                    $seenDerived,
                    'An explicit rule appeared after a derived point in '.$group['letter']
                );
            }
        }
    }
}
