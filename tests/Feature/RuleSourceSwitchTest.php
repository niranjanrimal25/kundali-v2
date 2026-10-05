<?php

namespace Tests\Feature;

use App\Models\Kundali;
use App\Models\User;
use App\Services\Astrology\Interpretation\ReadingGenerator;
use App\Services\Astrology\Interpretation\RuleRepository;
use Database\Seeders\InterpretationRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The owner/all switch decides which rules the engine may read.
 * Nothing is ever deleted, so reverting is a config change.
 */
class RuleSourceSwitchTest extends TestCase
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
    public function owner_mode_exposes_only_the_supplied_rules(): void
    {
        config()->set('jyotish.rule_sources', 'owner');

        $this->assertSame(['classical'], RuleRepository::activeProvenance());

        $repo = new RuleRepository('en');

        $this->assertSame(134, $repo->count());
    }

    #[Test]
    public function all_mode_exposes_the_whole_corpus(): void
    {
        config()->set('jyotish.rule_sources', 'all');

        $repo = new RuleRepository('en');

        $this->assertSame(1073, $repo->count());
    }

    #[Test]
    public function switching_modes_never_deletes_rules(): void
    {
        config()->set('jyotish.rule_sources', 'owner');
        (new RuleRepository('en'))->count();

        // Dormant rules remain in the database, ready to switch back on.
        $this->assertSame(1073, DB::table('interpretation_rules')->count());
    }

    #[Test]
    public function owner_mode_still_produces_a_complete_readable_report(): void
    {
        config()->set('jyotish.rule_sources', 'owner');

        $sections = app(ReadingGenerator::class)->forKundali($this->kundali(), 'en', true);

        // Every bhava must still be present, falling back to factual
        // statements where the owner corpus has nothing to say.
        foreach (range(1, 12) as $house) {
            $section = collect($sections)->firstWhere('key', "house_{$house}");

            $this->assertNotNull($section, "house_{$house} vanished in owner mode");
            $this->assertNotEmpty($section['paragraphs'], "house_{$house} rendered empty");
        }

        // The owner's own material must be present and substantial.
        $afflictions = collect($sections)->firstWhere('key', 'afflictions');

        $this->assertNotNull($afflictions);
        $this->assertGreaterThan(2, count($afflictions['paragraphs']));
    }

    #[Test]
    public function owner_mode_emits_no_text_from_the_projects_own_synthesis(): void
    {
        config()->set('jyotish.rule_sources', 'owner');

        $sections = app(ReadingGenerator::class)->forKundali($this->kundali(), 'en', true);
        $text = collect($sections)->flatMap(fn ($s) => $s['paragraphs'])->implode(' ');

        // Distinctive phrases that exist only in the modern-synthesis rules.
        foreach ([
            'emotional sensitivity, retentive memory',
            'delays marriage and brings a serious, dutiful',
            'Gaja Kesari',
        ] as $phrase) {
            $this->assertStringNotContainsString(
                $phrase,
                $text,
                "Owner mode leaked synthesis text: {$phrase}"
            );
        }
    }
}
