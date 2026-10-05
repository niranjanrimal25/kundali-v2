<?php

namespace Tests\Feature;

use App\Livewire\Kundali\FullReading;
use App\Models\Kundali;
use App\Models\Reading;
use App\Models\User;
use App\Services\Astrology\Interpretation\ReadingGenerator;
use App\Services\Astrology\Interpretation\RuleRepository;
use App\Services\Astrology\KundaliService;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\InterpretationRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReadingPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InterpretationRuleSeeder::class);
        $this->user = User::factory()->create();
    }

    private function kundali(?User $owner = null): Kundali
    {
        return Kundali::create([
            'user_id' => ($owner ?? $this->user)->id,
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
    public function it_offers_the_reading_as_a_file_download(): void
    {
        Livewire::actingAs($this->user)
            ->test(FullReading::class, ['kundali' => $this->kundali()])
            ->call('downloadPdf')
            ->assertFileDownloaded('demo-chart-kundali-reading.pdf');
    }

    #[Test]
    public function the_generated_file_is_a_real_pdf_containing_the_reading(): void
    {
        $kundali = $this->kundali();

        $facts = app(KundaliService::class)->facts($kundali);
        $sections = app(ReadingGenerator::class)->forKundali($kundali, 'en');

        $body = Pdf::loadView('pdf.reading', [
            'kundali' => $kundali,
            'facts' => $facts,
            'sections' => $sections,
            'ruleMode' => config('jyotish.rule_sources'),
        ])->setPaper('a4')->output();

        // A PDF, not an HTML error page.
        $this->assertStringStartsWith('%PDF-', $body);
        $this->assertGreaterThan(10_000, strlen($body));
    }

    #[Test]
    public function a_user_cannot_download_another_users_reading(): void
    {
        $theirs = $this->kundali(User::factory()->create());

        // mount() aborts 403, which Livewire surfaces as a forbidden
        // response rather than a thrown exception.
        Livewire::actingAs($this->user)
            ->test(FullReading::class, ['kundali' => $theirs])
            ->assertForbidden();
    }

    #[Test]
    public function a_cached_reading_is_reused_when_the_corpus_is_unchanged(): void
    {
        $kundali = $this->kundali();
        $generator = app(ReadingGenerator::class);

        $generator->forKundali($kundali, 'en', true);
        $first = Reading::first();

        $kundali->unsetRelation('readings');
        $generator->forKundali($kundali, 'en');

        $this->assertSame(
            $first->updated_at->toString(),
            Reading::first()->updated_at->toString(),
            'An unchanged corpus must not trigger a rebuild'
        );
    }

    #[Test]
    public function a_reading_rebuilds_itself_when_the_rule_sources_change(): void
    {
        config()->set('jyotish.rule_sources', 'owner');

        $kundali = $this->kundali();
        $generator = app(ReadingGenerator::class);

        $generator->forKundali($kundali, 'en', true);
        $ownerPrint = Reading::first()->corpus_fingerprint;

        // Switching sources must invalidate the stored reading without
        // anyone having to truncate the table by hand.
        config()->set('jyotish.rule_sources', 'all');
        $kundali->unsetRelation('readings');
        $generator->forKundali($kundali, 'en');

        $allPrint = Reading::first()->corpus_fingerprint;

        $this->assertNotSame($ownerPrint, $allPrint);
        $this->assertSame(RuleRepository::fingerprint('en'), $allPrint);
    }

    #[Test]
    public function a_reading_rebuilds_itself_when_rules_are_reseeded(): void
    {
        $kundali = $this->kundali();
        $generator = app(ReadingGenerator::class);

        $generator->forKundali($kundali, 'en', true);
        $before = Reading::first()->corpus_fingerprint;

        // Simulate a reseed changing the corpus.
        DB::table('interpretation_rules')->limit(5)->delete();

        $kundali->unsetRelation('readings');
        $generator->forKundali($kundali, 'en');

        $this->assertNotSame($before, Reading::first()->corpus_fingerprint);
    }
}
