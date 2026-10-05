<?php

namespace Tests\Feature;

use App\Livewire\Kundali\FullReading;
use App\Models\Kundali;
use App\Models\Reading;
use App\Models\User;
use App\Services\Astrology\KundaliService;
use App\Services\Astrology\Reading\ReadingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    public function the_generated_file_is_a_real_pdf(): void
    {
        $kundali = $this->kundali();

        $body = Pdf::loadView('pdf.reading', [
            'kundali' => $kundali,
            'facts' => app(KundaliService::class)->facts($kundali),
            'report' => app(ReadingService::class)->forKundali($kundali),
        ])->setPaper('a4')->output();

        $this->assertStringStartsWith('%PDF-', $body);
        $this->assertGreaterThan(10_000, strlen($body));
    }

    #[Test]
    public function a_user_cannot_reach_another_users_reading(): void
    {
        Livewire::actingAs($this->user)
            ->test(FullReading::class, ['kundali' => $this->kundali(User::factory()->create())])
            ->assertForbidden();
    }

    #[Test]
    public function a_cached_report_is_reused_when_the_rule_base_is_unchanged(): void
    {
        $kundali = $this->kundali();
        $service = app(ReadingService::class);

        $service->forKundali($kundali, 'en', true);
        $first = Reading::first();

        $kundali->unsetRelation('readings');
        $service->forKundali($kundali);

        $this->assertSame(
            $first->updated_at->toString(),
            Reading::first()->updated_at->toString(),
            'An unchanged rule base must not trigger a rebuild'
        );
    }

    #[Test]
    public function a_report_rebuilds_itself_when_a_rule_file_changes(): void
    {
        $kundali = $this->kundali();
        $service = app(ReadingService::class);

        $service->forKundali($kundali, 'en', true);
        $before = Reading::first()->corpus_fingerprint;

        // Editing a rule file must invalidate every stored report, with
        // no manual cache clearing required.
        $path = base_path('resources/rules/trik.json');
        $original = file_get_contents($path);

        try {
            file_put_contents($path, $original."\n");

            $kundali->unsetRelation('readings');
            $service->forKundali($kundali);

            $this->assertNotSame($before, Reading::first()->corpus_fingerprint);
        } finally {
            file_put_contents($path, $original);
        }
    }
}
