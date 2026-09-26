@php
    $facts = $this->facts();
    $meta = $facts['meta'];
    $lagna = $facts['lagna'];
    $current = $facts['dasha']['current'];
@endphp

<div class="py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        @if (session('status'))
            <div class="mb-6 rounded border-l-4 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="rounded border border-[#e3dccd] bg-gradient-to-br from-[#4a2c5a] via-[#7b3f61] to-[#b5643f] p-6 text-white shadow">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl" style="font-family:Georgia,serif;">{{ $kundali->name }}</h1>
                    <p class="mt-1 text-sm text-white/80">
                        {{ $kundali->birth_date->format('j F Y') }} at {{ $kundali->birth_time_short }}
                        · {{ $kundali->birth_place }}
                    </p>
                </div>
                <a href="{{ route('kundalis.index') }}" wire:navigate
                   class="rounded border border-white/30 px-3 py-1.5 text-xs text-white/90 hover:bg-white/10">
                    ← All Kundalis
                </a>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-4 border-t border-white/20 pt-4 text-sm sm:grid-cols-4">
                <div>
                    <div class="text-xs uppercase tracking-wide text-white/60">Lagna</div>
                    <div class="mt-0.5">{{ $lagna['sign_name'] }} <span class="text-white/70">{{ $lagna['degree_formatted'] }}</span></div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-white/60">Rashi (Moon)</div>
                    <div class="mt-0.5">{{ $facts['moon']['sign_name'] }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-white/60">Nakshatra</div>
                    <div class="mt-0.5">{{ $facts['moon']['nakshatra']['name'] }} <span class="text-white/70">pada {{ $facts['moon']['nakshatra']['pada'] }}</span></div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-white/60">Current Dasha</div>
                    <div class="mt-0.5">
                        @if ($current['mahadasha'])
                            {{ $current['mahadasha']['lord'] }}
                            @if ($current['antardasha']) / {{ $current['antardasha']['lord'] }} @endif
                        @else — @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Historical offset notice --}}
        @if ($meta['historical_offset_applied'])
            <div class="mt-4 rounded border-l-4 border-amber-400 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <strong>Historical timezone applied.</strong>
                This chart uses <strong>{{ $meta['utc_offset'] }}</strong>, the offset actually in force at
                {{ $kundali->birth_place }} on {{ $kundali->birth_date->format('j F Y') }} — not today's offset.
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-5">

            {{-- Chart --}}
            <div class="lg:col-span-2">
                <div class="rounded border border-[#e3dccd] bg-white p-5 shadow-sm">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-lg text-[#4a2c5a]" style="font-family:Georgia,serif;">
                            {{ $variant === 'D9' ? 'Navamsa (D9)' : 'Rashi Chart (D1)' }}
                        </h2>

                        <div class="flex gap-1">
                            <button wire:click="setVariant('D1')"
                                    class="rounded px-2 py-1 text-xs {{ $variant === 'D1' ? 'bg-[#4a2c5a] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">D1</button>
                            <button wire:click="setVariant('D9')"
                                    class="rounded px-2 py-1 text-xs {{ $variant === 'D9' ? 'bg-[#4a2c5a] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">D9</button>
                        </div>
                    </div>

                    <div class="flex justify-center">{!! $this->chartSvg !!}</div>

                    <div class="mt-4 flex justify-center gap-1">
                        <button wire:click="setStyle('north')"
                                class="rounded px-3 py-1.5 text-xs {{ $style === 'north' ? 'bg-[#4a2c5a] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            North Indian
                        </button>
                        <button wire:click="setStyle('south')"
                                class="rounded px-3 py-1.5 text-xs {{ $style === 'south' ? 'bg-[#4a2c5a] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            South Indian
                        </button>
                    </div>

                    <p class="mt-3 text-center text-xs text-gray-400">
                        ℞ retrograde · * combust
                    </p>
                </div>

                {{-- Calculation metadata --}}
                <div class="mt-4 rounded border border-[#e3dccd] bg-white p-5 text-xs shadow-sm">
                    <h3 class="mb-3 text-sm text-[#4a2c5a]" style="font-family:Georgia,serif;">Calculation Details</h3>
                    <dl class="space-y-1.5 text-gray-600">
                        <div class="flex justify-between gap-2"><dt class="text-gray-400">Ayanamsa</dt><dd>{{ $meta['ayanamsa_name'] }} {{ $meta['ayanamsa_formatted'] }}</dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-gray-400">House system</dt><dd>{{ $meta['house_system'] }}</dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-gray-400">Local time</dt><dd>{{ $meta['local_time'] }}</dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-gray-400">UTC offset</dt><dd>{{ $meta['utc_offset'] }}</dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-gray-400">Universal time</dt><dd>{{ $meta['utc'] }}</dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-gray-400">Julian Day</dt><dd>{{ number_format($meta['julian_day'], 5) }}</dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-gray-400">Coordinates</dt><dd>{{ $meta['latitude'] }}, {{ $meta['longitude'] }}</dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-gray-400">Engine</dt><dd>Swiss Ephemeris</dd></div>
                    </dl>

                    <button wire:click="recalculate"
                            class="mt-4 w-full rounded bg-[#f4f1ea] px-3 py-1.5 text-xs text-[#4a2c5a] hover:bg-[#ebe5d9]">
                        Recalculate from source data
                    </button>
                </div>
            </div>

            {{-- Planet table --}}
            <div class="lg:col-span-3">
                <div class="rounded border border-[#e3dccd] bg-white shadow-sm overflow-hidden">
                    <h2 class="border-b border-[#e3dccd] bg-[#faf7f0] px-5 py-3 text-lg text-[#4a2c5a]" style="font-family:Georgia,serif;">
                        Planetary Positions
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-[#f4f1ea] text-left text-xs uppercase tracking-wide text-gray-500">
                                    <th class="px-4 py-2 font-medium">Graha</th>
                                    <th class="px-3 py-2 font-medium">Sign</th>
                                    <th class="px-3 py-2 font-medium">Degree</th>
                                    <th class="px-2 py-2 font-medium text-center">Bhava</th>
                                    <th class="px-3 py-2 font-medium">Nakshatra</th>
                                    <th class="px-3 py-2 font-medium">Dignity</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($facts['planets'] as $name => $planet)
                                    <tr class="hover:bg-[#faf7f0]">
                                        <td class="px-4 py-2.5">
                                            <span class="text-gray-800">{{ $name }}</span>
                                            <span class="text-xs text-gray-400">{{ $planet['sanskrit'] }}</span>
                                            @if ($planet['retrograde'] && ! in_array($name, ['Rahu','Ketu']))
                                                <span class="ml-1 text-xs text-orange-600" title="Retrograde">℞</span>
                                            @endif
                                            @if ($planet['combust'])
                                                <span class="ml-1 text-xs text-red-500" title="Combust">*</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2.5 text-gray-700">{{ $planet['sign_name'] }}</td>
                                        <td class="px-3 py-2.5 text-gray-500 tabular-nums">{{ $planet['degree_formatted'] }}</td>
                                        <td class="px-2 py-2.5 text-center text-gray-700">{{ $planet['house'] }}</td>
                                        <td class="px-3 py-2.5 text-gray-600">
                                            {{ $planet['nakshatra']['name'] }}
                                            <span class="text-xs text-gray-400">{{ $planet['nakshatra']['pada'] }}</span>
                                        </td>
                                        <td class="px-3 py-2.5">
                                            @php
                                                $colour = match ($planet['dignity']) {
                                                    'exalted' => 'bg-emerald-100 text-emerald-800',
                                                    'moolatrikona', 'own' => 'bg-teal-100 text-teal-800',
                                                    'friendly' => 'bg-sky-100 text-sky-800',
                                                    'neutral' => 'bg-gray-100 text-gray-600',
                                                    'enemy' => 'bg-orange-100 text-orange-800',
                                                    'debilitated' => 'bg-red-100 text-red-800',
                                                    default => 'bg-gray-100 text-gray-600',
                                                };
                                            @endphp
                                            <span class="rounded px-1.5 py-0.5 text-xs {{ $colour }}">{{ $planet['dignity'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Houses --}}
                <div class="mt-6 rounded border border-[#e3dccd] bg-white shadow-sm overflow-hidden">
                    <h2 class="border-b border-[#e3dccd] bg-[#faf7f0] px-5 py-3 text-lg text-[#4a2c5a]" style="font-family:Georgia,serif;">
                        The Twelve Bhavas
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-[#f4f1ea] text-left text-xs uppercase tracking-wide text-gray-500">
                                    <th class="px-4 py-2 font-medium">House</th>
                                    <th class="px-3 py-2 font-medium">Sign</th>
                                    <th class="px-3 py-2 font-medium">Lord</th>
                                    <th class="px-3 py-2 font-medium">Lord placed in</th>
                                    <th class="px-3 py-2 font-medium">Occupants</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($facts['houses'] as $number => $house)
                                    <tr class="hover:bg-[#faf7f0]">
                                        <td class="px-4 py-2.5">
                                            <span class="text-gray-800">{{ $number }}</span>
                                            <span class="block text-xs text-gray-400">{{ $house['name'] }}</span>
                                        </td>
                                        <td class="px-3 py-2.5 text-gray-700">
                                            {{ $house['sign_name'] }}
                                            <span class="block text-xs text-gray-400">{{ $house['element'] }}</span>
                                        </td>
                                        <td class="px-3 py-2.5 text-gray-700">{{ $house['lord'] }}</td>
                                        <td class="px-3 py-2.5 text-gray-600">House {{ $house['lord_house'] }}</td>
                                        <td class="px-3 py-2.5 text-gray-600">
                                            {{ $house['occupants'] ? implode(', ', $house['occupants']) : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- The big button --}}
                <div class="mt-6 rounded border-2 border-[#b5643f] bg-gradient-to-r from-[#fffdf8] to-[#faf3ea] p-6 text-center">
                    <h3 class="text-lg text-[#4a2c5a]" style="font-family:Georgia,serif;">Ready for the full interpretation?</h3>
                    <p class="mx-auto mt-1 max-w-md text-sm text-gray-600">
                        Generate the complete astrologer-style reading of this chart — every house,
                        every graha, the yogas and doshas, the dasha timeline and the remedies.
                    </p>
                    <button type="button" disabled
                            class="mt-4 cursor-not-allowed rounded bg-[#b5643f] px-6 py-3 text-sm font-medium text-white opacity-60">
                        View Full Details of this Kundali
                    </button>
                    <p class="mt-2 text-xs text-gray-400">Interpretation engine arrives in the next phase.</p>
                </div>
            </div>
        </div>
    </div>
</div>
