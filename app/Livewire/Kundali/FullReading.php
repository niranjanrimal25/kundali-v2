<?php

namespace App\Livewire\Kundali;

use App\Models\Kundali;
use App\Services\Astrology\KundaliService;
use App\Services\Astrology\Reading\ReadingService;
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
    public function report(): array
    {
        return app(ReadingService::class)->forKundali($this->kundali, $this->locale);
    }

    /** Stream the reading as a PDF the user can keep or print. */
    public function downloadPdf()
    {
        abort_unless($this->kundali->user_id === auth()->id(), 403);

        $pdf = Pdf::loadView('pdf.reading', [
            'kundali' => $this->kundali,
            'facts' => $this->facts(),
            'report' => $this->report(),
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
