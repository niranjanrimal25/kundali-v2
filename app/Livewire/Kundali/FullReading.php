<?php

namespace App\Livewire\Kundali;

use App\Models\Kundali;
use App\Services\Astrology\Interpretation\ReadingGenerator;
use App\Services\Astrology\KundaliService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FullReading extends Component
{
    public Kundali $kundali;

    public string $locale = 'en';

    public function mount(Kundali $kundali): void
    {
        abort_unless($kundali->user_id === auth()->id(), 403);

        $this->kundali = $kundali;
    }

    #[Computed(persist: true)]
    public function sections(): array
    {
        return app(ReadingGenerator::class)->forKundali($this->kundali, $this->locale);
    }

    /** Stream the reading as a PDF the user can keep or print. */
    public function downloadPdf()
    {
        abort_unless($this->kundali->user_id === auth()->id(), 403);

        $pdf = Pdf::loadView('pdf.reading', [
            'kundali' => $this->kundali,
            'facts' => $this->facts(),
            'sections' => $this->sections(),
            'ruleMode' => config('jyotish.rule_sources'),
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
        app(ReadingGenerator::class)->forKundali($this->kundali, $this->locale, force: true);
        unset($this->sections);

        session()->flash('status', 'Reading regenerated from the current chart data.');
    }

    public function render()
    {
        return view('livewire.kundali.full-reading')->layout('layouts.app');
    }
}
