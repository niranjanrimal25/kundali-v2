<?php

namespace App\Livewire\Kundali;

use App\Models\Kundali;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class KundaliIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $kundali = Kundali::where('user_id', auth()->id())->findOrFail($id);
        $name = $kundali->name;
        $kundali->delete();

        session()->flash('status', "Deleted the Kundali for {$name}.");
    }

    public function render()
    {
        $kundalis = Kundali::query()
            ->where('user_id', auth()->id())
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('birth_place', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(12);

        return view('livewire.kundali.kundali-index', compact('kundalis'))
            ->layout('layouts.app');
    }
}
