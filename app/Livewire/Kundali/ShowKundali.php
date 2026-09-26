<?php

namespace App\Livewire\Kundali;

use App\Models\Kundali;
use App\Services\Astrology\ChartRenderer;
use App\Services\Astrology\KundaliService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ShowKundali extends Component
{
    public Kundali $kundali;

    /** 'north' or 'south' — North Indian is the default per spec. */
    public string $style = 'north';

    /** 'D1' (Rashi) or 'D9' (Navamsa). */
    public string $variant = 'D1';

    public function mount(Kundali $kundali): void
    {
        abort_unless($kundali->user_id === auth()->id(), 403);

        $this->kundali = $kundali;
    }

    #[Computed(persist: true)]
    public function facts(): array
    {
        return app(KundaliService::class)->facts($this->kundali);
    }

    #[Computed]
    public function chartSvg(): string
    {
        return app(ChartRenderer::class)->render($this->facts(), $this->style, $this->variant);
    }

    public function setStyle(string $style): void
    {
        $this->style = in_array($style, ['north', 'south'], true) ? $style : 'north';
    }

    public function setVariant(string $variant): void
    {
        $this->variant = in_array($variant, ['D1', 'D9'], true) ? $variant : 'D1';
    }

    public function recalculate(): void
    {
        app(KundaliService::class)->facts($this->kundali, force: true);
        unset($this->facts);

        session()->flash('status', 'Chart recalculated from source birth data.');
    }

    public function render()
    {
        return view('livewire.kundali.show-kundali')
            ->layout('layouts.app');
    }
}
