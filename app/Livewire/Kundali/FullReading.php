<?php

namespace App\Livewire\Kundali;

use App\Models\Kundali;
use App\Services\Astrology\KundaliService;
use App\Services\Astrology\Reading\ReadingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class FullReading extends Component
{
    public Kundali $kundali;

    #[Url(as: 'lang')]
    public string $locale = 'en';

    /** Locales the reading can be rendered in. */
    public const LOCALES = ['en' => 'English', 'ne' => 'नेपाली'];

    public function mount(Kundali $kundali): void
    {
        abort_unless($kundali->user_id === auth()->id(), 403);

        $this->kundali = $kundali;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = array_key_exists($locale, self::LOCALES) ? $locale : 'en';

        unset($this->report);
    }

    #[Computed(persist: true)]
    public function report(): array
    {
        return app(ReadingService::class)->forKundali($this->kundali, $this->locale);
    }

    /**
     * Stream the reading as a PDF.
     *
     * dompdf has no complex-script shaping, so Devanagari comes out as
     * question marks however the font is configured. Rather than hand
     * the user a broken file, a Nepali reading is exported in English
     * and the page says so. Replacing dompdf with mPDF, which does
     * shape Indic scripts, is the fix.
     */
    public function downloadPdf()
    {
        abort_unless($this->kundali->user_id === auth()->id(), 403);

        if ($this->locale !== 'en') {
            session()->flash('status', 'PDF export is English only for now. Devanagari needs a PDF engine with Indic text shaping, which is a separate change.');
        }

        $report = app(ReadingService::class)->forKundali($this->kundali, 'en');

        $pdf = Pdf::loadView('pdf.reading', [
            'kundali' => $this->kundali,
            'facts' => $this->facts(),
            'report' => $report,
            'locale' => 'en',
        ])->setPaper('a4');

        $name = Str::slug($this->kundali->name).'-kundali-reading.pdf';

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $name,
            ['Content-Type' => 'application/pdf']
        );
    }

    #[Computed]
    public function facts(): array
    {
        return app(KundaliService::class)->facts($this->kundali);
    }

    public function regenerate(): void
    {
        app(ReadingService::class)->forKundali($this->kundali, $this->locale, force: true);
        unset($this->report);

        session()->flash('status', 'Reading regenerated from the current chart data.');
    }

    public function render()
    {
        return view('livewire.kundali.full-reading')->layout('layouts.app');
    }
}
