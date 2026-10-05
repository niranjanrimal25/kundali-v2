@php($report = $this->report)

<div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

        @if (session('status'))
            <div class="mb-6 rounded border-l-4 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="rounded-t border border-[#e3dccd] bg-gradient-to-br from-[#4a2c5a] via-[#7b3f61] to-[#b5643f] px-8 py-6 text-white">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl" style="font-family:Georgia,serif;">{{ $kundali->name }}</h1>
                    <p class="mt-1 text-sm text-white/80">
                        Horoscope Reading &middot;
                        {{ $kundali->birth_date->format('j F Y') }} at {{ substr($kundali->birth_time, 0, 5) }}
                        &middot; {{ $kundali->birth_place }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button wire:click="downloadPdf" wire:loading.attr="disabled"
                            class="rounded bg-[#e9c46a] px-3 py-1.5 text-xs font-medium text-[#33203f] hover:bg-[#f0d089] disabled:opacity-60">
                        <span wire:loading.remove wire:target="downloadPdf">Download PDF</span>
                        <span wire:loading wire:target="downloadPdf">Preparing&hellip;</span>
                    </button>
                    <button onclick="window.print()"
                            class="rounded border border-white/30 px-3 py-1.5 text-xs text-white/90 hover:bg-white/10">
                        Print
                    </button>
                    <a href="{{ route('kundalis.show', $kundali) }}" wire:navigate
                       class="rounded border border-white/30 px-3 py-1.5 text-xs text-white/90 hover:bg-white/10">
                        &larr; Chart
                    </a>
                </div>
            </div>
        </div>

        {{-- Provenance notice --}}
        <div class="border-x border-[#e3dccd] bg-amber-50 px-8 py-3 text-xs text-amber-900">
            <strong>Source.</strong>
            Generated from the supplied rule base
            ({{ $report['stats']['explicit'] }} rules matched this chart).
            @if ($report['stats']['derived'] > 0)
                A further {{ $report['stats']['derived'] }} points are derived by composing the
                supplied Karakatwa against bhava significations. Those are folded away under each
                placement, and are not counted in the summary.
            @endif
        </div>

        <article class="rounded-b border-x border-b border-[#e3dccd] bg-[#fffdf8] px-8 py-8 shadow-sm">

            {{-- 1. Placements --}}
            <h2 class="text-xl text-[#4a2c5a]" style="font-family:Georgia,serif;">1. Chart Placement Overview</h2>
            <ul class="mt-4 space-y-2">
                @foreach ($report['placements'] as $p)
                    <li class="flex flex-wrap items-baseline gap-x-2 rounded border border-[#ede5d6] bg-[#f9f6ef] px-4 py-2.5 text-sm">
                        <span class="font-medium text-[#4a2c5a]">
                            @if ($p['isLagna']) Ascendant / Lagna &mdash; @endif {{ $p['ordinal'] }} House:
                        </span>
                        <span class="text-gray-700">{{ $p['signName'] }} ({{ $p['signSanskrit'] }} &mdash; {{ $p['signNumber'] }})</span>
                        <span class="text-gray-400">with</span>
                        @foreach ($p['grahas'] as $g)
                            <span class="rounded bg-[#4a2c5a] px-2 py-0.5 text-xs text-white">
                                {{ $g['sanskrit'] }}@if ($g['combust'])*@endif@if ($g['retrograde']) &#8478;@endif
                            </span>
                        @endforeach
                    </li>
                @endforeach
            </ul>

            {{-- 2. Analysis --}}
            <h2 class="mt-10 text-xl text-[#4a2c5a]" style="font-family:Georgia,serif;">
                2. Detailed Analysis Based On Your Rules
            </h2>
            <div class="mt-4 space-y-6">
                @foreach ($report['groups'] as $group)
                    <div class="rounded border border-[#ede5d6] bg-white px-5 py-4">
                        <h3 class="text-sm font-semibold text-[#4a2c5a]">{{ $group['letter'] }}. {{ $group['title'] }}</h3>
                        <p class="mt-1 text-sm font-medium text-[#b5643f]">{{ $group['heading'] }}</p>

                        {{-- Explicit rule matches: always visible --}}
                        @if ($group['explicit'] === [])
                            <p class="mt-2 text-sm italic text-gray-400">
                                No explicit rule in the rule base matches this placement.
                            </p>
                        @else
                            <ul class="mt-3 list-disc space-y-1.5 pl-5 text-sm leading-relaxed text-gray-700">
                                @foreach ($group['explicit'] as $point)
                                    <li>{{ $point['text'] }}</li>
                                @endforeach
                            </ul>
                        @endif

                        {{-- Derived Karakatwa: folded away so it never buries
                             the rules that actually matched. --}}
                        @if ($group['derived'] !== [])
                            <details class="group/acc mt-3 rounded border border-[#ede5d6] bg-[#f9f6ef]">
                                <summary class="flex cursor-pointer list-none items-center gap-2 px-3 py-2 text-xs font-medium text-[#7b3f61] hover:text-[#4a2c5a]">
                                    <span class="transition-transform group-open/acc:rotate-90">&#9656;</span>
                                    View Detailed Karakatwa &amp; House Breakdown
                                    <span class="text-gray-400">({{ count($group['derived']) }})</span>
                                </summary>
                                <ul class="list-disc space-y-1.5 border-t border-[#ede5d6] px-3 py-3 pl-8 text-sm leading-relaxed text-gray-600">
                                    @foreach ($group['derived'] as $point)
                                        <li>{{ $point['text'] }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- 3. Summary --}}
            @if ($report['summary'] !== [])
                <h2 class="mt-10 text-xl text-[#4a2c5a]" style="font-family:Georgia,serif;">3. Summary of Key Outcomes</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ($report['summary'] as $i => $bucket)
                        <div class="rounded border-l-4 border-[#7b3f61] bg-[#f9f6ef] px-4 py-3">
                            <h3 class="text-sm font-semibold text-[#4a2c5a]">{{ $i + 1 }}. {{ $bucket['label'] }}</h3>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-relaxed text-gray-700">
                                @foreach ($bucket['points'] as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="mt-8 border-t border-[#ede5d6] pt-4 text-xs leading-relaxed text-gray-500">
                Passages touching health describe tendencies indicated by the chart. They are not a
                medical opinion and cannot diagnose anything. If something here matches a symptom you
                actually have, treat it as a reason to see a doctor, not as a conclusion.
            </p>
        </article>
    </div>
</div>
