<div class="py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        @if (session('status'))
            <div class="mb-6 rounded border-l-4 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl text-[#4a2c5a]" style="font-family:Georgia,serif;">Saved Kundalis</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $kundalis->total() }} {{ Str::plural('chart', $kundalis->total()) }} stored
                </p>
            </div>

            <a href="{{ route('kundalis.create') }}" wire:navigate
               class="inline-flex items-center rounded bg-[#4a2c5a] px-4 py-2 text-sm font-medium text-white hover:bg-[#3a2245] transition">
                + New Kundali
            </a>
        </div>

        <div class="mb-5">
            <input type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Search by name or birth place…"
                   class="w-full sm:w-96 rounded border-gray-300 text-sm focus:border-[#4a2c5a] focus:ring-[#4a2c5a]">
        </div>

        @if ($kundalis->isEmpty())
            <div class="rounded border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
                <p class="text-gray-500">
                    @if ($search !== '')
                        No Kundali matches “{{ $search }}”.
                    @else
                        No Kundalis yet. Create your first chart to get started.
                    @endif
                </p>
                @if ($search === '')
                    <a href="{{ route('kundalis.create') }}" wire:navigate
                       class="mt-4 inline-block rounded bg-[#4a2c5a] px-4 py-2 text-sm text-white">Create a Kundali</a>
                @endif
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($kundalis as $kundali)
                    <div class="rounded border border-[#e3dccd] bg-white p-5 shadow-sm hover:shadow transition">
                        <div class="flex items-start justify-between gap-2">
                            <a href="{{ route('kundalis.show', $kundali) }}" wire:navigate
                               class="text-lg text-[#4a2c5a] hover:underline" style="font-family:Georgia,serif;">
                                {{ $kundali->name }}
                            </a>
                            <a href="{{ route('kundalis.edit', $kundali) }}" wire:navigate
                               class="text-xs text-gray-400 hover:text-[#4a2c5a]">Edit</a>

                            <button type="button"
                                    wire:click="delete({{ $kundali->id }})"
                                    wire:confirm="Delete the Kundali for {{ $kundali->name }}? This cannot be undone."
                                    class="text-xs text-gray-400 hover:text-red-600">Delete</button>
                        </div>

                        <dl class="mt-3 space-y-1 text-sm text-gray-600">
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-400">Born</dt>
                                <dd>{{ $kundali->birth_date->format('j M Y') }}, {{ $kundali->birth_time_short }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-400">Place</dt>
                                <dd class="text-right">{{ $kundali->birth_place }}</dd>
                            </div>
                            @if ($kundali->chartData)
                                <div class="flex justify-between gap-2">
                                    <dt class="text-gray-400">Lagna</dt>
                                    <dd>{{ \App\Services\Astrology\Support\Zodiac::SIGNS[$kundali->chartData->lagna_sign] }}</dd>
                                </div>
                            @endif
                        </dl>

                        <a href="{{ route('kundalis.show', $kundali) }}" wire:navigate
                           class="mt-4 block rounded bg-[#f4f1ea] px-3 py-2 text-center text-xs text-[#4a2c5a] hover:bg-[#ebe5d9]">
                            View Chart
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">{{ $kundalis->links() }}</div>
        @endif
    </div>
</div>
