@php
    $user = auth()->user();
    $kundalis = \App\Models\Kundali::where('user_id', $user->id)->latest()->take(4)->get();
    $total = \App\Models\Kundali::where('user_id', $user->id)->count();
    $readings = \App\Models\Reading::whereIn('kundali_id',
        \App\Models\Kundali::where('user_id', $user->id)->pluck('id'))->count();
    $rules = \Illuminate\Support\Facades\DB::table('interpretation_rules')->count();
@endphp

<x-app-layout>
    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-6 rounded border-l-4 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Greeting --}}
            <div class="relative overflow-hidden rounded-lg text-white shadow">
                <x-starfield />
                <div class="relative z-10 flex flex-wrap items-center justify-between gap-6 p-8">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-[#e9c46a]">
                            {{ now()->format('l, j F Y') }}
                        </p>
                        <h1 class="mt-2 text-3xl">Namaste, {{ $user->name }}</h1>
                        <p class="mt-2 max-w-lg text-sm text-white/70">
                            {{ $total === 0
                                ? 'You have not cast a chart yet. Start with your own birth details.'
                                : 'Your charts are ready. Open one to read all twelve bhavas in full.' }}
                        </p>
                    </div>

                    <a href="{{ route('kundalis.create') }}" wire:navigate
                       class="rounded-md bg-[#e9c46a] px-6 py-3 text-sm font-medium text-[#33203f] transition hover:bg-[#f0d089]">
                        + New Kundali
                    </a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['Charts saved', $total, '&#9770;'],
                    ['Readings generated', $readings, '&#9042;'],
                    ['Interpretation rules', number_format($rules), '&#9737;'],
                ] as [$label, $value, $glyph])
                    <div class="flex items-center gap-4 rounded-lg border border-[#e3dccd] bg-[#fffdf8] p-5">
                        <div class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-[#f4f1ea] text-xl text-[#7b3f61]">
                            {!! $glyph !!}
                        </div>
                        <div>
                            <div class="text-2xl text-[#4a2c5a]">{{ $value }}</div>
                            <div class="text-xs uppercase tracking-wider text-gray-500">{{ $label }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Recent charts --}}
            <div class="mt-8 flex items-center justify-between">
                <h2 class="text-xl text-[#4a2c5a]">Recent charts</h2>
                @if ($total > 0)
                    <a href="{{ route('kundalis.index') }}" wire:navigate
                       class="text-sm text-[#7b3f61] underline hover:text-[#4a2c5a]">View all {{ $total }}</a>
                @endif
            </div>

            @if ($kundalis->isEmpty())
                <div class="mt-4 rounded-lg border border-dashed border-[#d9d0be] bg-[#fffdf8] p-12 text-center">
                    <x-application-logo class="mx-auto h-12 w-12 text-[#d9d0be]" />
                    <h3 class="mt-4 text-lg text-[#4a2c5a]">No charts yet</h3>
                    <p class="mx-auto mt-2 max-w-sm text-sm text-gray-500">
                        You need a birth date, an exact time and a place. Four minutes of error
                        shifts the Lagna by roughly one degree, so check the birth certificate.
                    </p>
                    <a href="{{ route('kundalis.create') }}" wire:navigate
                       class="mt-6 inline-block rounded-md bg-[#4a2c5a] px-6 py-2.5 text-sm text-white hover:bg-[#3a2245]">
                        Cast your first chart
                    </a>
                </div>
            @else
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ($kundalis as $k)
                        <a href="{{ route('kundalis.show', $k) }}" wire:navigate
                           class="group flex items-start gap-4 rounded-lg border border-[#e3dccd] bg-[#fffdf8] p-5 transition hover:border-[#7b3f61]/40 hover:shadow">
                            <div class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-[#4a2c5a] text-[#e9c46a]">
                                {{ mb_substr($k->name, 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <div class="truncate text-lg text-[#4a2c5a] group-hover:underline">{{ $k->name }}</div>
                                <div class="mt-1 text-sm text-gray-500">
                                    {{ $k->birth_date->format('j M Y') }} · {{ substr($k->birth_time, 0, 5) }}
                                </div>
                                <div class="truncate text-xs text-gray-400">{{ $k->birth_place }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
