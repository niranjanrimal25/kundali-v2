<?php

namespace Tests\Feature;

use App\Livewire\Kundali\FullReading;
use App\Models\Kundali;
use App\Models\User;
use App\Services\Astrology\Interpretation\NarrativeComposer;
use App\Services\Astrology\Interpretation\ReadingGenerator;
use App\Services\Astrology\Interpretation\RuleRepository;
use Database\Seeders\InterpretationRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReadingGeneratorTest extends TestCase
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
            'name' => 'Reading Subject',
            'gender' => 'male',
            'birth_date' => '1990-05-15',
            'birth_time' => '10:30',
            'birth_place' => 'Pokhara, NP',
            'latitude' => 28.26689,
            'longitude' => 83.96851,
            'timezone' => 'Asia/Kathmandu',
        ]);
    }

    #[Test]
    public function it_seeds_the_full_rule_corpus(): void
    {
        $this->assertDatabaseCount('interpretation_rules', 709);

        // Every bhava needs its complete lord-placement and sign layers.
        foreach (range(1, 12) as $house) {
            $this->assertGreaterThanOrEqual(
                12,
                \DB::table('interpretation_rules')
                    ->where('condition_type', 'lord_in_house')
                    ->where('condition_key', 'like', $house.':%')
                    ->count(),
                "House {$house} is missing lord-placement rules"
            );

            $this->assertSame(
                12,
                \DB::table('interpretation_rules')
                    ->where('condition_type', 'house_sign')
                    ->where('condition_key', 'like', $house.':%')
                    ->count(),
                "House {$house} is missing house-sign rules"
            );
        }

        $this->assertDatabaseCount('interpretation_rules', 709);
    }

    #[Test]
    public function it_generates_all_expected_sections(): void
    {
        $sections = app(ReadingGenerator::class)->forKundali($this->kundali());

        $keys = array_column($sections, 'key');

        $this->assertSame(
            array_merge(
                ['overview'],
                array_map(fn ($h) => "house_{$h}", range(1, 12)),
                ['dasha'],
            ),
            $keys
        );

        foreach ($sections as $section) {
            $this->assertNotEmpty($section['title']);
            $this->assertNotEmpty($section['paragraphs'], "{$section['key']} has no prose");

            foreach ($section['paragraphs'] as $paragraph) {
                $this->assertGreaterThan(40, strlen($paragraph), 'Paragraph is suspiciously short');
                $this->assertStringEndsWith('.', trim($paragraph));
            }
        }
    }

    #[Test]
    public function the_reading_is_specific_to_this_chart_not_generic(): void
    {
        $sections = app(ReadingGenerator::class)->forKundali($this->kundali());
        $text = collect($sections)->pluck('paragraphs')->flatten()->join(' ');

        // Real chart facts must appear in the prose.
        $this->assertStringContainsString('Karka', $text, 'Lagna sign missing');
        $this->assertStringContainsString('12°18', $text, 'Exact ascendant degree missing');
        $this->assertStringContainsString('Uttara Ashadha', $text, 'Moon nakshatra missing');
        $this->assertStringContainsString('Digbala', $text, 'Directional strength not discussed');
    }

    #[Test]
    public function it_persists_the_reading_and_reuses_it(): void
    {
        $kundali = $this->kundali();

        $this->assertDatabaseCount('readings', 0);

        $first = app(ReadingGenerator::class)->forKundali($kundali);
        $this->assertDatabaseCount('readings', 1);

        $second = app(ReadingGenerator::class)->forKundali($kundali);

        // A second call must reuse the stored reading, not duplicate it.
        $this->assertDatabaseCount('readings', 1);
        $this->assertSame($first, $second);
    }

    #[Test]
    public function the_same_chart_always_reads_identically(): void
    {
        $generator = app(ReadingGenerator::class);

        $a = $generator->forKundali($this->kundali(), 'en', true);
        $b = $generator->forKundali($this->kundali(), 'en', true);

        $this->assertSame($a, $b, 'Narrative variation must be deterministic per chart');
    }

    #[Test]
    public function no_placeholder_or_broken_text_leaks_into_the_prose(): void
    {
        $sections = app(ReadingGenerator::class)->forKundali($this->kundali());
        $text = collect($sections)->pluck('paragraphs')->flatten()->join(' ');

        // Regressions caught during the pilot build.
        $this->assertStringNotContainsString('It and it', $text, 'Conjunction collision in modifier chain');
        $this->assertStringNotContainsString('  ', $text, 'Double space');
        $this->assertStringNotContainsString(' .', $text, 'Space before full stop');
        $this->assertStringNotContainsString('..', $text, 'Doubled full stop');
        $this->assertStringNotContainsString('%s', $text, 'Unfilled format placeholder');
        $this->assertDoesNotMatchRegularExpression('/\bthe the\b/i', $text);
        $this->assertDoesNotMatchRegularExpression('/,\s*,/', $text);
    }

    #[Test]
    public function the_composer_joins_conflicting_fragments_concessively(): void
    {
        $composer = new NarrativeComposer('seed');

        $paragraphs = $composer->compose([
            ['text' => 'This placement is strongly favourable', 'polarity' => 2, 'weight' => 80],
            ['text' => 'it also brings sustained difficulty', 'polarity' => -2, 'weight' => 80],
        ]);

        $text = implode(' ', $paragraphs);

        // A positive statement followed by a negative one must NOT be
        // joined additively, or the paragraph contradicts itself.
        $this->assertStringNotContainsString('Moreover', $text);
        $this->assertStringNotContainsString('In addition', $text);

        $this->assertMatchesRegularExpression(
            '/(That said|Yet|Even so|At the same time|Set against this|Working in the other direction)/',
            $text,
            'Expected a concessive connector between conflicting fragments'
        );
    }

    #[Test]
    public function the_composer_caps_modifiers_to_avoid_run_on_sentences(): void
    {
        $composer = new NarrativeComposer('seed');

        $sentence = $composer->planetSentence('Saturn', 'Shani sits in the 7th', [
            'rests in its own sign',
            'is retrograde',
            'holds full directional strength',
        ]);

        // Three modifiers must break across sentences, not chain with commas.
        $this->assertGreaterThanOrEqual(3, substr_count($sentence, '.'));
        $this->assertStringNotContainsString('It and', $sentence);
    }

    #[Test]
    public function the_reading_page_renders_and_is_protected(): void
    {
        $kundali = $this->kundali();

        Livewire::actingAs($this->user)
            ->test(FullReading::class, ['kundali' => $kundali])
            ->assertOk()
            ->assertSee('Horoscope Reading')
            ->assertSee('Overview')
            ->assertSee('Marriage')
            ->assertSee('Current Planetary Period');

        $other = User::factory()->create();

        Livewire::actingAs($other)
            ->test(FullReading::class, ['kundali' => $kundali])
            ->assertForbidden();
    }

    #[Test]
    public function the_chart_page_links_to_the_reading(): void
    {
        $kundali = $this->kundali();

        $this->actingAs($this->user)
            ->get(route('kundalis.show', $kundali))
            ->assertOk()
            ->assertSee('View Full Details of this Kundali')
            ->assertSee(route('kundalis.reading', $kundali), false);
    }

    #[Test]
    public function every_dasha_fragment_reads_as_a_grammatical_sentence(): void
    {
        $rules = new RuleRepository('en');
        $generator = app(ReadingGenerator::class);

        $leadIn = new \ReflectionMethod($generator, 'dashaLeadIn');
        $leadIn->setAccessible(true);

        $planets = ['Sun', 'Moon', 'Mars', 'Mercury', 'Jupiter', 'Venus', 'Saturn', 'Rahu', 'Ketu'];

        foreach ($planets as $planet) {
            foreach (range(1, 12) as $house) {
                $rule = $rules->first('dasha_lord_house', "{$planet}:{$house}");

                $this->assertNotNull($rule, "No dasha rule for {$planet} in house {$house}");

                $sentence = $leadIn->invoke($generator, $rule->text).$rule->text;

                // "During this period, a period of upheaval" is the failure
                // mode this guards: a noun phrase given a verb-phrase lead-in.
                $this->assertDoesNotMatchRegularExpression(
                    '/^During this period, (?:a|an|the|one of|among)\b/i',
                    $sentence,
                    "Ungrammatical lead-in for {$planet}:{$house} — {$sentence}"
                );

                $this->assertDoesNotMatchRegularExpression(
                    '/^The period brings (?:a|an|the|strongly|excellent)\b/i',
                    $sentence,
                    "Ungrammatical lead-in for {$planet}:{$house} — {$sentence}"
                );
            }
        }
    }

    #[Test]
    public function an_empty_house_is_described_rather_than_skipped(): void
    {
        // Born so that the 7th has no occupant — verify graceful handling.
        $kundali = Kundali::create([
            'user_id' => $this->user->id,
            'name' => 'Empty House Subject',
            'birth_date' => '1995-03-10',
            'birth_time' => '14:15',
            'birth_place' => 'Kathmandu, NP',
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'timezone' => 'Asia/Kathmandu',
        ]);

        $sections = app(ReadingGenerator::class)->forKundali($kundali);

        foreach ($sections as $section) {
            $this->assertNotEmpty(
                $section['paragraphs'],
                "Section {$section['key']} produced no prose"
            );
        }
    }
}
