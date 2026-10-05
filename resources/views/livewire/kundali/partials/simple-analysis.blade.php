{{-- Point-by-point analysis: the same rule corpus, laid out as grouped
     findings rather than flowing prose. --}}
<article class="rounded-b border-x border-b border-[#e3dccd] bg-[#fffdf8] px-8 py-8 shadow-sm">

    {{-- 1. Placements --}}
    <section>
        <h2 class="text-xl text-[#4a2c5a]" style="font-family:Georgia,serif;">
            1. Chart Placement Overview
        </h2>
        <p class="mt-1 text-xs text-gray-500">
            Only bhavas that hold a graha are listed.
        </p>

        <ul class="mt-4 space-y-2">
            @foreach ($simple['placements'] as $p)
                <li class="flex flex-wrap items-baseline gap-x-2 rounded border border-[#ede5d6] bg-[#f9f6ef] px-4 py-2.5 text-sm">
                    <span class="font-medium text-[#4a2c5a]">
                        @if ($p['is_lagna']) Ascendant / Lagna &mdash; @endif
                        {{ $p['ordinal'] }} House:
                    </span>
                    <span class="text-gray-700">
                        {{ $p['sign_name'] }} ({{ $p['sign_sanskrit'] }} &mdash; {{ $p['sign_number'] }})
                    </span>
                    <span class="text-gray-400">with</span>
                    @foreach ($p['grahas'] as $g)
                        <span class="rounded bg-[#4a2c5a] px-2 py-0.5 text-xs text-white">
                            {{ $g['sanskrit'] }}@if ($g['combust'])<span title="Combust">*</span>@endif
                            @if ($g['retrograde'])<span title="Retrograde">&#8478;</span>@endif
                        </span>
                    @endforeach
                </li>
            @endforeach
        </ul>
    </section>

    {{-- 2. Analysis --}}
    <section class="mt-10">
        <h2 class="text-xl text-[#4a2c5a]" style="font-family:Georgia,serif;">
            2. Detailed Analysis Based On Your Rules
        </h2>

        <div class="mt-4 space-y-6">
            @foreach ($simple['analysis'] as $group)
                <div class="rounded border border-[#ede5d6] bg-white px-5 py-4">
                    <h3 class="text-sm font-semibold text-[#4a2c5a]">
                        {{ $group['letter'] }}. {{ $group['title'] }}
                    </h3>

                    <p class="mt-1 text-sm font-medium text-[#b5643f]">{{ $group['heading'] }}</p>

                    @if ($group['points'] === [])
                        <p class="mt-2 text-sm italic text-gray-400">
                            No rule in the active corpus speaks to this placement.
                        </p>
                    @else
                        <ul class="mt-3 list-disc space-y-1.5 pl-5 text-sm leading-relaxed text-gray-700">
                            @foreach ($group['points'] as $point)
                                <li>{{ $point }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- 3. Summary --}}
    @if ($simple['summary'] !== [])
        <section class="mt-10">
            <h2 class="text-xl text-[#4a2c5a]" style="font-family:Georgia,serif;">
                3. Summary of Key Outcomes
            </h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($simple['summary'] as $i => $bucket)
                    <div class="rounded border-l-4 border-[#7b3f61] bg-[#f9f6ef] px-4 py-3">
                        <h3 class="text-sm font-semibold text-[#4a2c5a]">
                            {{ $i + 1 }}. {{ $bucket['label'] }}
                        </h3>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-relaxed text-gray-700">
                            @foreach ($bucket['points'] as $point)
                                <li>{{ $point }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <p class="mt-8 border-t border-[#ede5d6] pt-4 text-xs leading-relaxed text-gray-500">
        Passages touching health describe tendencies indicated by the chart. They are not a
        medical opinion and cannot diagnose anything. If something here matches a symptom you
        actually have, treat it as a reason to see a doctor, not as a conclusion.
    </p>
</article>
