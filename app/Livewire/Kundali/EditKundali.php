<?php

namespace App\Livewire\Kundali;

use App\Models\City;
use App\Models\Kundali;
use App\Services\Astrology\TimeResolver;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EditKundali extends Component
{
    public Kundali $kundali;

    public string $name = '';

    public string $gender = '';

    public string $birth_date = '';

    public string $birth_time = '';

    public string $place_query = '';

    public string $birth_place = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public string $timezone = '';

    public string $notes = '';

    public bool $place_selected = true;

    /**
     * The birth inputs as loaded. Compared on save to decide whether the
     * cached chart and reading must be thrown away.
     */
    private array $original = [];

    public function mount(Kundali $kundali): void
    {
        abort_unless($kundali->user_id === auth()->id(), 403);

        $this->kundali = $kundali;

        $this->name = $kundali->name;
        $this->gender = $kundali->gender ?? '';
        $this->birth_date = $kundali->birth_date->format('Y-m-d');
        $this->birth_time = substr($kundali->birth_time, 0, 5);
        $this->birth_place = $kundali->birth_place;
        $this->place_query = $kundali->birth_place;
        $this->latitude = (float) $kundali->latitude;
        $this->longitude = (float) $kundali->longitude;
        $this->timezone = $kundali->timezone;
        $this->notes = $kundali->notes ?? '';
    }

    #[Computed]
    public function placeSuggestions()
    {
        if ($this->place_selected || mb_strlen(trim($this->place_query)) < 2) {
            return collect();
        }

        return City::search($this->place_query)->limit(8)->get();
    }

    public function updatedPlaceQuery(): void
    {
        $this->place_selected = false;
    }

    public function selectPlace(int $cityId): void
    {
        $city = City::findOrFail($cityId);

        $this->birth_place = $city->label;
        $this->place_query = $city->label;
        $this->latitude = $city->latitude;
        $this->longitude = $city->longitude;
        $this->timezone = $city->timezone;
        $this->place_selected = true;

        $this->resetValidation(['birth_place', 'latitude', 'longitude', 'timezone']);
    }

    public function clearPlace(): void
    {
        $this->reset(['birth_place', 'latitude', 'longitude', 'timezone', 'place_selected', 'place_query']);
    }

    #[Computed]
    public function offsetPreview(): ?array
    {
        if ($this->timezone === '' || $this->birth_date === '' || $this->birth_time === '') {
            return null;
        }

        try {
            $resolver = app(TimeResolver::class);

            return [
                'label' => $resolver->offsetLabel($this->birth_date, $this->birth_time, $this->timezone),
                'historical' => $resolver->usedHistoricalOffset($this->birth_date, $this->birth_time, $this->timezone),
                'utc' => $resolver->toUtc($this->birth_date, $this->birth_time, $this->timezone)->format('Y-m-d H:i'),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /** Whether the pending edit would change the chart itself. */
    #[Computed]
    public function willRecalculate(): bool
    {
        return $this->birth_date !== $this->kundali->birth_date->format('Y-m-d')
            || $this->birth_time !== substr($this->kundali->birth_time, 0, 5)
            || (float) $this->latitude !== (float) $this->kundali->latitude
            || (float) $this->longitude !== (float) $this->kundali->longitude
            || $this->timezone !== $this->kundali->timezone;
    }

    public function save()
    {
        abort_unless($this->kundali->user_id === auth()->id(), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', ''])],
            'birth_date' => ['required', 'date', 'before_or_equal:today', 'after:1800-01-01'],
            'birth_time' => ['required', 'date_format:H:i'],
            'birth_place' => ['required', 'string', 'max:200'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'timezone' => ['required', 'timezone'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'birth_place.required' => 'Please search for and select a birth place.',
            'timezone.required' => 'A timezone is required — select a place from the list.',
            'birth_time.date_format' => 'Birth time must be in 24-hour HH:MM format.',
        ]);

        // Decide BEFORE writing, while the model still holds old values.
        $mustRecalculate = $this->willRecalculate();

        $this->kundali->update([
            'name' => $validated['name'],
            'gender' => $validated['gender'] ?: null,
            'birth_date' => $validated['birth_date'],
            'birth_time' => $validated['birth_time'],
            'birth_place' => $validated['birth_place'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'timezone' => $validated['timezone'],
            'notes' => $validated['notes'] ?: null,
        ]);

        // A changed birth moment invalidates every derived artefact. Not
        // doing this would silently serve a chart belonging to the old
        // birth details, which is the worst possible failure here.
        if ($mustRecalculate) {
            $this->kundali->invalidateDerivedData();

            session()->flash('status', "Updated {$this->kundali->name}. The chart and reading were recalculated.");
        } else {
            session()->flash('status', "Updated {$this->kundali->name}.");
        }

        return $this->redirect(route('kundalis.show', $this->kundali), navigate: true);
    }

    public function delete()
    {
        abort_unless($this->kundali->user_id === auth()->id(), 403);

        $name = $this->kundali->name;
        $this->kundali->delete();

        session()->flash('status', "Deleted the Kundali for {$name}.");

        return $this->redirect(route('kundalis.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.kundali.edit-kundali')->layout('layouts.app');
    }
}
