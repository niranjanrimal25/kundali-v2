@props(['class' => ''])

{{-- Decorative night sky with a slowly rotating zodiac ring.
     Pure inline SVG so it renders with no network access. --}}
<div {{ $attributes->merge(['class' => 'pointer-events-none absolute inset-0 overflow-hidden '.$class]) }} aria-hidden="true">
    <svg class="h-full w-full" viewBox="0 0 600 900" preserveAspectRatio="xMidYMid slice">
        <defs>
            <radialGradient id="sky" cx="50%" cy="30%" r="80%">
                <stop offset="0%" stop-color="#6b3f6e"/>
                <stop offset="55%" stop-color="#3d2350"/>
                <stop offset="100%" stop-color="#241436"/>
            </radialGradient>
            <radialGradient id="glow" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#e9c46a" stop-opacity=".45"/>
                <stop offset="100%" stop-color="#e9c46a" stop-opacity="0"/>
            </radialGradient>
        </defs>

        <rect width="600" height="900" fill="url(#sky)"/>
        <circle cx="300" cy="300" r="260" fill="url(#glow)"/>

        {{-- Star field --}}
        <g fill="#fff">
            @php
                // Deterministic pseudo-random so the sky never flickers between renders.
                $seed = 20240515;
                $stars = [];
                for ($i = 0; $i < 90; $i++) {
                    $seed = ($seed * 1103515245 + 12345) % 2147483648;
                    $x = $seed % 600;
                    $seed = ($seed * 1103515245 + 12345) % 2147483648;
                    $y = $seed % 900;
                    $seed = ($seed * 1103515245 + 12345) % 2147483648;
                    $r = round(0.4 + ($seed % 100) / 100 * 1.4, 2);
                    $seed = ($seed * 1103515245 + 12345) % 2147483648;
                    $o = round(0.25 + ($seed % 100) / 100 * 0.7, 2);
                    $stars[] = [$x, $y, $r, $o];
                }
            @endphp
            @foreach ($stars as [$x, $y, $r, $o])
                <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $r }}" opacity="{{ $o }}"/>
            @endforeach
        </g>

        {{-- Zodiac ring --}}
        <g transform="translate(300,300)" opacity=".5">
            <g style="transform-origin:center; animation: kundali-spin 120s linear infinite;">
                <circle r="200" fill="none" stroke="#e9c46a" stroke-width="1" opacity=".55"/>
                <circle r="168" fill="none" stroke="#e9c46a" stroke-width=".6" opacity=".35"/>
                @for ($i = 0; $i < 12; $i++)
                    <line x1="0" y1="-200" x2="0" y2="-168"
                          stroke="#e9c46a" stroke-width="1" opacity=".6"
                          transform="rotate({{ $i * 30 }})"/>
                    <circle cx="0" cy="-184" r="2.4" fill="#e9c46a" opacity=".8"
                            transform="rotate({{ $i * 30 + 15 }})"/>
                @endfor
            </g>

            {{-- Inner square-and-diamond, the North Indian chart skeleton --}}
            <g opacity=".65" stroke="#f4f1ea" fill="none" stroke-width="1.1">
                <rect x="-96" y="-96" width="192" height="192"/>
                <path d="M-96,-96 L96,96 M96,-96 L-96,96"/>
                <path d="M0,-96 L96,0 L0,96 L-96,0 Z"/>
            </g>
        </g>
    </svg>

    <style>
        @keyframes kundali-spin { from { transform: rotate(0deg) } to { transform: rotate(360deg) } }
        @media (prefers-reduced-motion: reduce) {
            [style*="kundali-spin"] { animation: none !important; }
        }
    </style>
</div>
