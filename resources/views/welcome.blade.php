<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Kundali') }} &mdash; Vedic birth charts, read properly</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{font-family:Georgia,'Times New Roman',serif}</style>
</head>
<body class="bg-[#f4f1ea] text-[#2b2520] antialiased">

    {{-- ===== Hero ===== --}}
    <header class="relative overflow-hidden text-white">
        <x-starfield />

        <div class="relative z-10 mx-auto max-w-6xl px-6 py-6">
            <nav class="flex items-center justify-between">
                <a href="/" class="inline-flex items-center gap-3">
                    <x-application-logo class="h-9 w-9 text-[#e9c46a]" />
                    <span class="text-lg tracking-wide">{{ config('app.name', 'Kundali') }}</span>
                </a>

                <div class="flex items-center gap-3 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" wire:navigate
                           class="rounded-md border border-white/30 px-4 py-2 hover:bg-white/10">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="px-3 py-2 text-white/80 hover:text-white">Log in</a>
                        <a href="{{ route('register') }}"
                           class="rounded-md bg-[#e9c46a] px-4 py-2 font-medium text-[#33203f] hover:bg-[#f0d089]">Get started</a>
                    @endauth
                </div>
            </nav>
        </div>

        <div class="relative z-10 mx-auto grid max-w-6xl items-center gap-12 px-6 pb-24 pt-10 lg:grid-cols-2">
            <div>
                <p class="mb-4 inline-block rounded-full border border-[#e9c46a]/40 px-3 py-1 text-xs uppercase tracking-[0.2em] text-[#e9c46a]">
                    Swiss Ephemeris &middot; Lahiri ayanamsa
                </p>

                <h1 class="text-4xl leading-tight sm:text-5xl">
                    Your Kundali, calculated to the arcsecond &mdash;
                    <span class="text-[#e9c46a]">and actually explained.</span>
                </h1>

                <p class="mt-6 max-w-xl text-base leading-relaxed text-white/75">
                    Most free chart sites give you a diagram and a paragraph of horoscope filler.
                    This one computes real planetary positions, then reads all twelve bhavas the way
                    an astrologer would &mdash; naming the cause, not just the symptom.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}"
                       class="rounded-md bg-[#e9c46a] px-6 py-3 font-medium text-[#33203f] transition hover:bg-[#f0d089]">
                        Cast your chart
                    </a>
                    <a href="{{ route('login') }}"
                       class="rounded-md border border-white/30 px-6 py-3 text-white/90 transition hover:bg-white/10">
                        I already have an account
                    </a>
                </div>

                <dl class="mt-12 grid grid-cols-3 gap-6 border-t border-white/10 pt-6 text-sm">
                    <div>
                        <dt class="text-2xl text-[#e9c46a]">0.6&Prime;</dt>
                        <dd class="mt-1 text-white/60">from published Lahiri</dd>
                    </div>
                    <div>
                        <dt class="text-2xl text-[#e9c46a]">939</dt>
                        <dd class="mt-1 text-white/60">interpretation rules</dd>
                    </div>
                    <div>
                        <dt class="text-2xl text-[#e9c46a]">&#8377;0</dt>
                        <dd class="mt-1 text-white/60">no paid APIs, ever</dd>
                    </div>
                </dl>
            </div>

            {{-- North Indian chart illustration --}}
            <div class="hidden justify-center lg:flex">
                <div class="rounded-lg border border-white/15 bg-white/5 p-6 backdrop-blur-sm">
                    <svg viewBox="0 0 320 320" class="h-80 w-80">
                        <rect x="10" y="10" width="300" height="300" fill="none" stroke="#e9c46a" stroke-width="1.5" opacity=".8"/>
                        <path d="M10,10 L310,310 M310,10 L10,310" stroke="#e9c46a" stroke-width="1" opacity=".55"/>
                        <path d="M160,10 L310,160 L160,310 L10,160 Z" fill="none" stroke="#e9c46a" stroke-width="1.2" opacity=".75"/>
                        @php
                            $marks = [
                                [160,60,'Ke'], [85,40,'—'], [40,85,'—'], [60,160,'Mo'],
                                [40,235,'—'], [85,280,'—'], [160,260,'Sa Ra'], [235,280,'Ma'],
                                [280,235,'—'], [260,160,'Me'], [280,85,'Su'], [235,40,'Ju'],
                            ];
                        @endphp
                        @foreach ($marks as [$x,$y,$label])
                            <text x="{{ $x }}" y="{{ $y }}" fill="#f4f1ea" font-size="13"
                                  font-family="Georgia,serif" text-anchor="middle" opacity=".9">{{ $label }}</text>
                        @endforeach
                        <text x="160" y="165" fill="#e9c46a" font-size="11" font-family="Georgia,serif"
                              text-anchor="middle" opacity=".7">Cancer Lagna</text>
                    </svg>
                </div>
            </div>
        </div>
    </header>

    {{-- ===== What it does ===== --}}
    <section class="mx-auto max-w-6xl px-6 py-20">
        <h2 class="text-center text-3xl text-[#4a2c5a]">What you actually get</h2>
        <p class="mx-auto mt-3 max-w-2xl text-center text-sm text-gray-500">
            Four layers, computed from your exact birth moment and place.
        </p>

        <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            @php
                $features = [
                    ['&#9737;', 'Accurate positions', 'Swiss Ephemeris with Lahiri ayanamsa, verified against the published reference to within a fraction of an arcsecond.'],
                    ['&#9779;', 'Both chart styles', 'North Indian diamond by default, South Indian grid on a toggle. Rendered as clean SVG, printable at any size.'],
                    ['&#9042;', 'All twelve bhavas', 'Sign, lord placement, occupants, dignity, direction and drishti — composed into flowing prose, not bullet points.'],
                    ['&#8986;', 'Dasha and transits', 'Vimshottari mahadasha and antardasha with dates, plus a live Sade Sati check against transiting Saturn.'],
                ];
            @endphp

            @foreach ($features as [$glyph, $title, $body])
                <div class="rounded-lg border border-[#e3dccd] bg-[#fffdf8] p-6 transition hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-[#4a2c5a] text-xl text-[#e9c46a]">
                        {!! $glyph !!}
                    </div>
                    <h3 class="mt-4 text-lg text-[#4a2c5a]">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===== Sample reading ===== --}}
    <section class="border-y border-[#e3dccd] bg-[#fffdf8] py-20">
        <div class="mx-auto max-w-4xl px-6">
            <p class="text-center text-xs uppercase tracking-[0.25em] text-[#b5643f]">A real excerpt</p>
            <h2 class="mt-3 text-center text-3xl text-[#4a2c5a]">This is how it reads</h2>

            <blockquote class="mt-10 rounded-lg border-l-4 border-[#b5643f] bg-[#f4f1ea] p-8 text-[15px] leading-[1.85]">
                <p class="font-semibold text-[#4a2c5a]">7th Bhava &mdash; Marriage &amp; Partnership</p>
                <p class="mt-4">
                    Shani in the 7th delays marriage and brings a serious, dutiful, frequently older
                    partner. The early years are demanding, but this placement rewards persistence:
                    the bond becomes one of the most durable in the chart. It is retrograde, and holds
                    full directional strength (Digbala) in the west &mdash; the quarter of partnership.
                </p>
                <p class="mt-4">
                    Shani with Rahu is a heavy combination, producing prolonged obstruction and
                    ambition pursued through difficult means. It also confers formidable resilience.
                </p>
            </blockquote>

            <p class="mt-6 text-center text-sm text-gray-500">
                Every claim traces back to a placement in your chart &mdash; and the reading says which one.
            </p>
        </div>
    </section>

    {{-- ===== Honesty section ===== --}}
    <section class="mx-auto max-w-4xl px-6 py-20">
        <div class="rounded-lg border border-[#e3dccd] bg-[#fffdf8] p-8">
            <h2 class="text-xl text-[#4a2c5a]">What this is, and what it isn't</h2>
            <div class="mt-6 grid gap-8 sm:grid-cols-2 text-sm leading-relaxed">
                <div>
                    <p class="font-semibold text-emerald-800">Genuinely classical</p>
                    <p class="mt-2 text-gray-600">
                        The computation &mdash; exaltation degrees, moolatrikona, drishti, digbala,
                        Vimshottari &mdash; follows standard Parashari method and is independently verified.
                    </p>
                </div>
                <div>
                    <p class="font-semibold text-[#b5643f]">A modern synthesis</p>
                    <p class="mt-2 text-gray-600">
                        The interpretive prose is written in Parashari style but is not a translation of
                        any classical text. It is not quoted from Brihat Parashara Hora Shastra, and it
                        does not claim to be.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== CTA ===== --}}
    <section class="relative overflow-hidden text-white">
        <x-starfield />
        <div class="relative z-10 mx-auto max-w-3xl px-6 py-20 text-center">
            <h2 class="text-3xl">Cast your first chart</h2>
            <p class="mx-auto mt-4 max-w-xl text-white/70">
                You need a date, a time and a place. Four minutes of error moves the Lagna
                by about a degree, so use the birth certificate where you can.
            </p>
            <a href="{{ route('register') }}"
               class="mt-8 inline-block rounded-md bg-[#e9c46a] px-8 py-3 font-medium text-[#33203f] transition hover:bg-[#f0d089]">
                Get started &mdash; free
            </a>
        </div>
    </section>

    <footer class="bg-[#241436] py-8 text-center text-xs text-white/40">
        <p>{{ config('app.name', 'Kundali') }} &middot; Positions by Swiss Ephemeris &middot; Lahiri (Chitra Paksha) ayanamsa</p>
    </footer>
</body>
</html>
