<?php

namespace Tests\Feature;

use App\Livewire\Kundali\FullReading;
use App\Models\Kundali;
use App\Models\User;
use App\Services\Astrology\Reading\ReadingService;
use App\Services\Astrology\Reading\RuleBase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NepaliTranslationTest extends TestCase
{
    use RefreshDatabase;

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
    public function a_translation_overlays_only_the_text_never_the_conditions(): void
    {
        $base = new RuleBase;

        $english = collect($base->localised('karakatwa', 'en'))->keyBy('id');
        $nepali = collect($base->localised('karakatwa', 'ne'))->keyBy('id');

        foreach ($english as $id => $en) {
            $ne = $nepali[$id];

            // Structure must survive translation untouched.
            $this->assertSame($en['subject'], $ne['subject']);
            $this->assertSame($en['facet'], $ne['facet']);
            $this->assertSame($en['category'], $ne['category']);

            // Text must actually differ.
            $this->assertNotSame($en['text'], $ne['text'], "{$id} was not translated");
        }
    }

    #[Test]
    public function an_untranslated_rule_falls_back_to_english(): void
    {
        $base = new RuleBase;

        // Composite rules have no Nepali file yet.
        $rules = $base->localised('composite', 'ne');

        $this->assertNotEmpty($rules);

        foreach ($rules as $rule) {
            $this->assertFalse($rule['translated'] ?? false);
            $this->assertNotEmpty($rule['then']['text'], 'Fallback must keep the English text, not blank it');
        }
    }

    #[Test]
    public function the_nepali_report_renders_in_devanagari(): void
    {
        $report = app(ReadingService::class)->forKundali($this->kundali(), 'ne', true);

        $this->assertStringContainsString('१. ग्रह स्थिति सारांश', $report['markdown']);
        $this->assertStringContainsString('२. तपाईंका नियम अनुसार विस्तृत विश्लेषण', $report['markdown']);

        $labels = collect($report['summary'])->pluck('label')->all();
        $this->assertContains('स्वास्थ्य र शरीर', $labels);
    }

    #[Test]
    public function derived_sentences_use_the_nepali_frame(): void
    {
        $report = app(ReadingService::class)->forKundali($this->kundali(), 'ne', true);

        $derived = [];

        foreach ($report['groups'] as $group) {
            foreach ($group['points'] as $point) {
                if ($point['derived']) {
                    $derived[] = $point['text'];
                }
            }
        }

        $this->assertNotEmpty($derived);

        // The frame and the karakatwa must both be Nepali.
        $joined = implode(' ', $derived);
        $this->assertStringContainsString('भावमा', $joined);
        $this->assertStringContainsString('मुटु', $joined, 'Sun body karakatwa should appear in Nepali');
    }

    #[Test]
    public function english_and_nepali_reports_are_cached_separately(): void
    {
        $kundali = $this->kundali();
        $service = app(ReadingService::class);

        $en = $service->forKundali($kundali, 'en', true);
        $kundali->unsetRelation('readings');
        $ne = $service->forKundali($kundali, 'ne', true);

        $this->assertNotSame($en['markdown'], $ne['markdown']);
        $this->assertDatabaseCount('readings', 2);
    }

    #[Test]
    public function the_reading_page_offers_a_language_switch(): void
    {
        $kundali = $this->kundali();

        Livewire::actingAs($kundali->user)
            ->test(FullReading::class, ['kundali' => $kundali])
            ->assertSet('locale', 'en')
            ->call('setLocale', 'ne')
            ->assertSet('locale', 'ne')
            ->call('setLocale', 'klingon')
            ->assertSet('locale', 'en');
    }
}
