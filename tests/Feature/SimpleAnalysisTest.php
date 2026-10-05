<?php

namespace Tests\Feature;

use App\Livewire\Kundali\FullReading;
use App\Models\Kundali;
use App\Models\User;
use App\Services\Astrology\Interpretation\SimpleAnalysisGenerator;
use App\Services\Astrology\KundaliService;
use Database\Seeders\InterpretationRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SimpleAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InterpretationRuleSeeder::class);
        $this->user = User::factory()->create();
    }

    private function kundali(): Kundali
    {
        return Kundali::create([
            'user_id' => $this->user->id,
            'name' => 'Demo Chart',
            'birth_date' => '1990-05-15',
            'birth_time' => '10:30:00',
            'birth_place' => 'Pokhara, Nepal',
            'latitude' => 28.26689,
            'longitude' => 83.96851,
            'timezone' => 'Asia/Kathmandu',
        ]);
    }

    private function analysis(): array
    {
        $facts = app(KundaliService::class)->facts($this->kundali(), true);

        return app(SimpleAnalysisGenerator::class)->generate($facts);
    }

    #[Test]
    public function it_lists_only_occupied_houses(): void
    {
        $facts = app(KundaliService::class)->facts($this->kundali(), true);
        $analysis = app(SimpleAnalysisGenerator::class)->generate($facts);

        $occupied = collect($facts['planets'])->pluck('house')->unique()->sort()->values()->all();
        $listed = collect($analysis['placements'])->pluck('house')->sort()->values()->all();

        $this->assertSame($occupied, $listed, 'Empty bhavas must not be listed');
    }

    #[Test]
    public function grahas_sharing_a_bhava_are_merged_into_one_group(): void
    {
        $analysis = $this->analysis();

        // Saturn and Rahu share the 7th in this chart.
        $seventh = collect($analysis['analysis'])->firstWhere('house', 7);

        $this->assertNotNull($seventh);
        $this->assertStringContainsString('Conjunction', $seventh['title']);
        $this->assertStringContainsString('Shani', $seventh['heading']);
        $this->assertStringContainsString('Rahu', $seventh['heading']);
    }

    #[Test]
    public function every_graha_appears_exactly_once_across_the_groups(): void
    {
        $facts = app(KundaliService::class)->facts($this->kundali(), true);
        $analysis = app(SimpleAnalysisGenerator::class)->generate($facts);

        $headings = collect($analysis['analysis'])->pluck('heading')->implode(' ');

        foreach ($facts['planets'] as $planet) {
            $this->assertStringContainsString(
                $planet['sanskrit'],
                $headings,
                "{$planet['sanskrit']} is missing from the analysis"
            );
        }
    }

    #[Test]
    public function a_graha_in_a_trik_bhava_fires_on_placement_alone(): void
    {
        $analysis = $this->analysis();

        // Jupiter sits in the 12th. It is NOT afflicted, yet the owner's
        // Trik rules must still fire: placement alone is the trigger.
        $twelfth = collect($analysis['analysis'])->firstWhere('house', 12);

        $this->assertNotNull($twelfth);
        $this->assertNotEmpty($twelfth['points']);

        // Inside a group the finding is introduced by "According to your
        // rules, ...", so the facet lead-in is lower-cased there.
        $text = implode(' ', $twelfth['points']);
        $this->assertStringContainsStringIgnoringCase('physically, the indications are', $text);
    }

    #[Test]
    public function the_summary_groups_findings_under_the_standard_categories(): void
    {
        $analysis = $this->analysis();

        $labels = collect($analysis['summary'])->pluck('label')->all();

        $this->assertNotEmpty($labels);

        foreach ($labels as $label) {
            $this->assertContains($label, array_values(SimpleAnalysisGenerator::CATEGORIES));
        }
    }

    #[Test]
    public function the_reading_page_offers_both_views(): void
    {
        Livewire::actingAs($this->user)
            ->test(FullReading::class, ['kundali' => $this->kundali()])
            ->assertSee('Full Reading')
            ->assertSee('Point-by-Point Analysis')
            ->assertSet('view', 'full')
            ->call('setView', 'simple')
            ->assertSet('view', 'simple')
            ->assertSee('Chart Placement Overview')
            ->assertSee('Detailed Analysis Based On Your Rules')
            ->assertSee('Summary of Key Outcomes');
    }

    #[Test]
    public function an_unknown_view_falls_back_to_the_full_reading(): void
    {
        Livewire::actingAs($this->user)
            ->test(FullReading::class, ['kundali' => $this->kundali()])
            ->call('setView', 'nonsense')
            ->assertSet('view', 'full');
    }
}
