<div class="py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-6">
            <a href="{{ route('kundalis.show', $kundali) }}" wire:navigate
               class="text-xs text-gray-500 hover:text-gray-800">&larr; Back to chart</a>

            <h1 class="mt-2 text-2xl text-[#4a2c5a] mb-1" style="font-family:Georgia,serif;">
                Edit {{ $kundali->name }}
            </h1>
            <p class="text-sm text-gray-500">
                Correcting the birth moment changes the chart itself. Any edit to date,
                time or place discards the stored chart and reading so both are rebuilt
                from the corrected details.
            </p>
        </div>

        {{-- Warn before saving, not after --}}
        @if ($this->willRecalculate)
            <div class="mb-4 rounded border-l-4 border-amber-500 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <strong>This edit changes the chart.</strong>
                The birth moment or place differs from what was saved, so the Kundali
                and its full reading will be recalculated when you save.
            </div>
        @endif

        <form wire:submit="save" class="space-y-6 rounded border border-[#e3dccd] bg-white p-6 shadow-sm">

            {{-- Name & gender --}}
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Full name</label>
                    <input type="text" wire:model="name"
                           class="mt-1 w-full rounded border-gray-300 text-sm focus:border-[#4a2c5a] focus:ring-[#4a2c5a]">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Gender</label>
                    <select wire:model="gender"
                            class="mt-1 w-full rounded border-gray-300 text-sm focus:border-[#4a2c5a] focus:ring-[#4a2c5a]">
                        <option value="">—</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                    @error('gender') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Date & time --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date of birth</label>
                    <input type="date" wire:model.live="birth_date" max="{{ now()->toDateString() }}"
                           class="mt-1 w-full rounded border-gray-300 text-sm focus:border-[#4a2c5a] focus:ring-[#4a2c5a]">
                    @error('birth_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Time of birth <span class="text-gray-400 font-normal">(24-hour)</span>
                    </label>
                    <input type="time" wire:model.live="birth_time"
                           class="mt-1 w-full rounded border-gray-300 text-sm focus:border-[#4a2c5a] focus:ring-[#4a2c5a]">
                    @error('birth_time') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-400">
                        Four minutes of error shifts the Lagna by about one degree.
                    </p>
                </div>
            </div>

            {{-- Place with live autocomplete --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Place of birth</label>

                <div class="relative mt-1">
                    <input type="text" wire:model.live.debounce.250ms="place_query"
                           placeholder="Start typing, e.g. Pokhara…"
                           autocomplete="off"
                           class="w-full rounded border-gray-300 text-sm focus:border-[#4a2c5a] focus:ring-[#4a2c5a]">

                    @if ($this->placeSuggestions->isNotEmpty())
                        <ul class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded border border-gray-200 bg-white shadow-lg">
                            @foreach ($this->placeSuggestions as $city)
                                <li>
                                    <button type="button" wire:click="selectPlace({{ $city->id }})"
                                            class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-[#f4f1ea]">
                                        <span>
                                            <span class="text-gray-800">{{ $city->name }}</span>
                                            <span class="text-gray-400">· {{ $city->country_code }}</span>
                                        </span>
                                        <span class="text-xs text-gray-400">{{ $city->timezone }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @error('birth_place') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Resolved coordinates --}}
            @if ($place_selected || $latitude !== null)
                <div class="rounded bg-[#f4f1ea] p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-[#4a2c5a]">Resolved coordinates</h3>
                        <button type="button" wire:click="clearPlace"
                                class="text-xs text-gray-500 hover:text-gray-800">Change place</button>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs text-gray-500">Latitude</label>
                            <input type="number" step="0.000001" wire:model.live="latitude"
                                   class="mt-1 w-full rounded border-gray-300 text-sm">
                            @error('latitude') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Longitude</label>
                            <input type="number" step="0.000001" wire:model.live="longitude"
                                   class="mt-1 w-full rounded border-gray-300 text-sm">
                            @error('longitude') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Timezone</label>
                            <input type="text" wire:model.live="timezone" readonly
                                   class="mt-1 w-full rounded border-gray-300 bg-gray-50 text-sm text-gray-600">
                            @error('timezone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Live offset preview: the single biggest accuracy safeguard --}}
                    @if ($preview = $this->offsetPreview)
                        <div class="mt-3 border-t border-[#e0d8c8] pt-3 text-xs">
                            <div class="flex flex-wrap gap-x-6 gap-y-1 text-gray-600">
                                <span>UTC offset applied: <strong class="text-[#4a2c5a]">{{ $preview['label'] }}</strong></span>
                                <span>Universal time: <strong class="text-[#4a2c5a]">{{ $preview['utc'] }}</strong></span>
                            </div>

                            @if ($preview['historical'])
                                <p class="mt-2 rounded bg-amber-50 px-2 py-1.5 text-amber-800">
                                    <strong>Historical offset applied.</strong>
                                    This birth predates the current offset for {{ $timezone }}.
                                    For Nepal, births before 1 January 1986 correctly use +05:30 rather than +05:45.
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea wire:model="notes" rows="2"
                          class="mt-1 w-full rounded border-gray-300 text-sm focus:border-[#4a2c5a] focus:ring-[#4a2c5a]"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                <a href="{{ route('kundalis.show', $kundali) }}" wire:navigate
                   class="text-sm text-gray-500 hover:text-gray-800">Cancel</a>

                <button type="submit"
                        class="rounded bg-[#4a2c5a] px-5 py-2 text-sm font-medium text-white hover:bg-[#3a2245] disabled:opacity-50"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save changes</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </div>
        </form>

        {{-- Destructive actions kept visually separate from the form --}}
        <div class="mt-8 rounded border border-red-200 bg-red-50/50 p-5">
            <h2 class="text-sm font-semibold text-red-900">Delete this Kundali</h2>
            <p class="mt-1 text-xs text-red-800/80">
                Permanently removes {{ $kundali->name }}, along with the stored chart and
                the generated reading. This cannot be undone.
            </p>

            <button type="button"
                    wire:click="delete"
                    wire:confirm="Delete the Kundali for {{ $kundali->name }}? This cannot be undone."
                    class="mt-3 rounded border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-600 hover:text-white">
                Delete permanently
            </button>
        </div>
    </div>
</div>
