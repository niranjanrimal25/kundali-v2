<?php

namespace Tests\Feature;

use App\Models\Kundali;
use App\Models\User;
use App\Services\Astrology\Interpretation\ReadingGenerator;
use App\Services\Astrology\Interpretation\RuleRepository;
use App\Services\Astrology\KundaliService;
use Database\Seeders\InterpretationRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YogaDoshaTest extends TestCase
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
    public function the_golden_chart_detects_its_known_yogas(): void
    {
        $facts = app(KundaliService::class)->facts($this->kundali(), true);

        $keys = array_column($facts['yogas'], 'key');

        // Saturn: own sign, 7th house (a kendra) -> Sasa Yoga.
        $this->assertContains('panchamahapurusha_sasa', $keys);
        $this->assertContains('gaja_kesari', $keys);
        $this->assertContains('vipareeta_raja', $keys);

        foreach ($facts['yogas'] as $yoga) {
            $this->assertNotEmpty($yoga['basis'], "{$yoga['key']} states no factual basis");
            $this->assertContains($yoga['strength'], ['strong', 'moderate', 'qualified']);
        }
    }

    #[Test]
    public function a_cancelled_dosha_is_reported_as_cancelled(): void
    {
        $facts = app(KundaliService::class)->facts($this->kundali(), true);

        $mangal = collect($facts['doshas'])->firstWhere('key', 'mangal_dosha');

        $this->assertNotNull($mangal, 'Mars in the 8th must raise Mangal Dosha');
        $this->assertTrue($mangal['cancelled'], 'Jupiter aspects Mars, so it must be cancelled');
        $this->assertNotEmpty($mangal['cancellation']);
        $this->assertSame('mitigated', $mangal['severity']);
    }

    #[Test]
    public function every_detectable_yoga_and_dosha_has_a_rule(): void
    {
        $rules = new RuleRepository('en');

        $yogaKeys = [
            'panchamahapurusha_ruchaka', 'panchamahapurusha_bhadra',
            'panchamahapurusha_hamsa', 'panchamahapurusha_malavya',
            'panchamahapurusha_sasa', 'gaja_kesari', 'budha_aditya',
            'chandra_mangala', 'kemadruma', 'vipareeta_raja',
            'raja_yoga', 'dhana_yoga',
        ];

        foreach ($yogaKeys as $key) {
            $this->assertNotNull(
                $rules->first('yoga', $key.':moderate'),
                "Detector can emit {$key} but no rule exists for it"
            );
        }

        foreach (['mangal_dosha', 'mangal_dosha_mitigated', 'kaal_sarpa',
            'grahan_dosha_sun', 'grahan_dosha_moon', 'kemadruma_affliction'] as $key) {
            $this->assertNotNull($rules->first('dosha', $key), "No rule for dosha {$key}");
        }

        foreach (['sade_sati_first', 'sade_sati_peak', 'sade_sati_final',
            'kantaka_shani', 'ashtama_shani'] as $key) {
            $this->assertNotNull($rules->first('transit', $key), "No rule for transit {$key}");
        }
    }

    #[Test]
    public function the_reading_includes_a_yoga_section_with_its_basis(): void
    {
        $sections = app(ReadingGenerator::class)->forKundali($this->kundali(), 'en', true);

        $yoga = collect($sections)->firstWhere('key', 'yogas');

        $this->assertNotNull($yoga, 'Reading must contain a yoga section');
        $this->assertNotEmpty($yoga['paragraphs']);

        $text = implode(' ', $yoga['paragraphs']);

        // Claims must carry their evidence.
        $this->assertStringContainsString('kendra', $text);
        $this->assertStringContainsString('Sasa Yoga', $text);

        // A cancelled dosha must not read as an active threat.
        $this->assertStringContainsString('cancelled', $text);
    }

    #[Test]
    public function a_transit_failure_cannot_break_the_birth_chart(): void
    {
        $facts = app(KundaliService::class)->facts($this->kundali(), true);

        // Transits are computed live; the contract is that the key always
        // exists and never throws, even if the ephemeris call degrades.
        $this->assertArrayHasKey('transits', $facts);
        $this->assertIsArray($facts['transits']);
    }
}
