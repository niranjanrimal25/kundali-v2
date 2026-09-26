<?php

namespace App\Livewire\Kundali;

use App\Models\City;
use App\Models\Kundali;
use App\Services\Astrology\TimeResolver;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CreateKundali extends Component
{
    public string $name = '';

    public string $gender = '';

    public string $birth_date = '';

    public string $birth_time = '';

    public string $place_query = '';

    public string $birth_place = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public string $timezone = '';

    /** Set when the user overrides the looked-up coordinates by hand. */
    public bool $manual_coordinates = false;

    public string $notes = '';

    public bool $place_selected = false;

    /**
     * Live autocomplete against the offline GeoNames table.
     * Only runs once the query is specific enough to be useful.
     */
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

    /**
     * Preview of the UTC offset that will be applied, shown live so the
     * user can sanity-check it before generating — especially important
     * for pre-1986 Nepali births where +5:30 is correct, not +5:45.
     */
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

    public function save()
    {
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

        $kundali = Kundali::create([
            'user_id' => auth()->id(),
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

        session()->flash('status', "Kundali generated for {$kundali->name}.");

        return $this->redirect(route('kundalis.show', $kundali), navigate: true);
    }

    public function render()
    {
        return view('livewire.kundali.create-kundali')
            ->layout('layouts.app');
    }
}
