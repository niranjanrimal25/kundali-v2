<?php

namespace App\Livewire\Kundali;

use App\Models\Kundali;
use App\Services\Astrology\KundaliService;
use App\Services\Astrology\Reading\ReadingService;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Mpdf\Mpdf;

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
     * mPDF rather than dompdf, because dompdf has no complex-script
     * shaping and rendered Devanagari as question marks whatever font
     * was supplied. mPDF performs Indic shaping, so a Nepali reading
     * exports in Nepali.
     */
    public function downloadPdf()
    {
        abort_unless($this->kundali->user_id === auth()->id(), 403);

        $html = view('pdf.reading', [
            'kundali' => $this->kundali,
            'facts' => $this->facts(),
            'report' => $this->report(),
            'locale' => $this->locale,
        ])->render();

        $pdf = new Mpdf([
            'format' => 'A4',
            'margin_left' => 18,
            'margin_right' => 18,
            'margin_top' => 20,
            'margin_bottom' => 18,
            // mPDF ships Devanagari-capable fonts; autoScriptToLang and
            // autoLangToFont pick them per run of text.
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => storage_path('app/mpdf'),
            'default_font' => $this->locale === 'ne' ? 'freeserif' : 'dejavuserif',
        ]);

        $pdf->SetTitle($this->kundali->name.' — Kundali Reading');
        $pdf->WriteHTML($html);

        $name = Str::slug($this->kundali->name).'-kundali-reading.pdf';

        return response()->streamDownload(
            fn () => print ($pdf->Output('', 'S')),
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
