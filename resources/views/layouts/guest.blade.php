<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Kundali') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            body { font-family: Georgia, 'Times New Roman', serif; }
        </style>
    </head>
    <body class="antialiased text-[#2b2520]">
        <div class="flex min-h-screen bg-[#f4f1ea]">

            {{-- Brand panel --}}
            <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden p-12 text-white lg:flex">
                <x-starfield />

                <div class="relative z-10">
                    <a href="{{ url('/') }}" wire:navigate class="inline-flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full border border-[#e9c46a]/60 text-lg text-[#e9c46a]">&#9770;</span>
                        <span class="text-xl tracking-wide">{{ config('app.name', 'Kundali') }}</span>
                    </a>
                </div>

                <div class="relative z-10 max-w-md">
                    <h2 class="text-3xl leading-snug">
                        A birth chart calculated properly, and read like an astrologer would read it.
                    </h2>
                    <p class="mt-4 text-sm leading-relaxed text-white/70">
                        Swiss Ephemeris positions, Lahiri ayanamsa, whole-sign bhavas and
                        Vimshottari dasha &mdash; then nine hundred interpretation rules
                        turn all of it into plain language.
                    </p>

                    <div class="mt-8 flex gap-8 text-xs uppercase tracking-widest text-[#e9c46a]/80">
                        <div>
                            <div class="text-2xl text-white">0.6&Prime;</div>
                            <div class="mt-1">ayanamsa accuracy</div>
                        </div>
                        <div>
                            <div class="text-2xl text-white">939</div>
                            <div class="mt-1">reading rules</div>
                        </div>
                        <div>
                            <div class="text-2xl text-white">12</div>
                            <div class="mt-1">bhavas read</div>
                        </div>
                    </div>
                </div>

                <p class="relative z-10 text-xs text-white/40">
                    Ephemeris by Swiss Ephemeris &middot; Lahiri (Chitra Paksha) ayanamsa
                </p>
            </div>

            {{-- Form panel --}}
            <div class="flex w-full flex-col justify-center px-6 py-12 sm:px-12 lg:w-1/2">
                <div class="mx-auto w-full max-w-md">

                    <a href="{{ url('/') }}" wire:navigate class="mb-8 inline-flex items-center gap-2 lg:hidden">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#4a2c5a] text-[#e9c46a]">&#9770;</span>
                        <span class="text-lg text-[#4a2c5a]">{{ config('app.name', 'Kundali') }}</span>
                    </a>

                    <div class="rounded-lg border border-[#e3dccd] bg-[#fffdf8] p-8 shadow-sm">
                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-center text-xs text-gray-400">
                        Your birth details stay on your own server.
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
