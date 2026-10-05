@php
    $facts = $this->facts();
    $sections = $this->sections();
@endphp

<div class="py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        @if (session('status'))
            <div class="mb-6 rounded border-l-4 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="rounded-t border border-[#e3dccd] bg-gradient-to-br from-[#4a2c5a] via-[#7b3f61] to-[#b5643f] px-8 py-7 text-white">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="text-xs uppercase tracking-[3px] text-white/60">Horoscope Reading</div>
                    <h1 class="mt-2 text-3xl" style="font-family:Georgia,serif;">{{ $kundali->name }}</h1>
                    <p class="mt-1 text-sm text-white/80">
                        {{ $kundali->birth_date->format('j F Y') }} at {{ $kundali->birth_time_short }}
                        · {{ $kundali->birth_place }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('kundalis.show', $kundali) }}" wire:navigate
                       class="rounded border border-white/30 px-3 py-1.5 text-xs text-white/90 hover:bg-white/10">
                        ← Chart
                    </a>
                    <button onclick="window.print()"
                            class="rounded border border-white/30 px-3 py-1.5 text-xs text-white/90 hover:bg-white/10">
                        Print
                    </button>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-x-8 gap-y-2 border-t border-white/20 pt-4 text-sm">
                <span><span class="text-white/60">Lagna</span> {{ $facts['lagna']['sign_name'] }} {{ $facts['lagna']['degree_formatted'] }}</span>
                <span><span class="text-white/60">Rashi</span> {{ $facts['moon']['sign_name'] }}</span>
                <span><span class="text-white/60">Nakshatra</span> {{ $facts['moon']['nakshatra']['name'] }}</span>
                <span><span class="text-white/60">Ayanamsa</span> {{ $facts['meta']['ayanamsa_name'] }}</span>
            </div>
        </div>

        {{-- Scope notice: states which rule corpus produced this reading,
             so a thin report is never mistaken for a broken one. --}}
        @php($ruleMode = config('jyotish.rule_sources'))

        <div class="border-x border-[#e3dccd] bg-amber-50 px-8 py-3 text-xs text-amber-900">
            @if ($ruleMode === 'owner')
                <strong>Source.</strong>
                This reading is generated <em>only</em> from the rule set supplied by the
                owner of this installation. Placements with no supplied rule are stated as
                plain chart facts rather than interpreted, so some bhavas will read briefly.
            @else
                <strong>Scope.</strong>
                This reading covers the Lagna, all twelve Bhavas, yogas and doshas, and the
                running Vimshottari dasha. Remedial measures are analysed in a later release.
            @endif
        </div>

        {{-- The reading --}}
        <article class="rounded-b border-x border-b border-[#e3dccd] bg-[#fffdf8] px-8 py-8 shadow-sm">
            @foreach ($sections as $section)
                <section class="{{ $loop->first ? '' : 'mt-10 border-t border-[#ede5d6] pt-8' }}">
                    <h2 class="text-2xl text-[#4a2c5a]" style="font-family:Georgia,serif;">
                        {{ $section['title'] }}
                    </h2>

                    @if (!empty($section['subtitle']))
                        <p class="mt-1 text-xs uppercase tracking-wide text-[#b5643f]">
                            {{ $section['subtitle'] }}
                        </p>
                    @endif

                    <div class="mt-4 space-y-4">
                        @foreach ($section['paragraphs'] as $paragraph)
                            <p class="text-[15px] leading-[1.75] text-[#2b2520]" style="font-family:Georgia,serif;">
                                {{ $paragraph }}
                            </p>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div class="mt-10 border-t border-[#ede5d6] pt-5 text-xs text-gray-400">
                <p>
                    Computed with Swiss Ephemeris using the {{ $facts['meta']['ayanamsa_name'] }} ayanamsa
                    ({{ $facts['meta']['ayanamsa_formatted'] }}) and {{ $facts['meta']['house_system'] }} houses.
                    Universal time {{ $facts['meta']['utc'] }}, offset {{ $facts['meta']['utc_offset'] }}.
                </p>
                <button wire:click="regenerate" class="mt-3 text-[#4a2c5a] underline hover:no-underline">
                    Regenerate this reading
                </button>
            </div>
        </article>
    </div>
</div>
