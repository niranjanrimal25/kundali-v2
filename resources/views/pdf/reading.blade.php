<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $kundali->name }} — Kundali Reading</title>
    <style>
        @page { margin: 22mm 18mm 20mm 18mm; }

        body {
            font-family: DejaVu Serif, serif;
            font-size: 10.5pt;
            line-height: 1.65;
            color: #2b2520;
        }

        .cover { text-align: center; padding-top: 38mm; }
        .cover h1 { font-size: 26pt; color: #4a2c5a; margin: 0 0 6pt; }
        .cover .sub { font-size: 11pt; color: #7b3f61; margin-bottom: 24pt; }

        .facts { margin: 0 auto; width: 80%; border-collapse: collapse; }
        .facts td { padding: 4pt 8pt; font-size: 10pt; border-bottom: 0.5pt solid #e3dccd; text-align: left; }
        .facts td.k { color: #7b3f61; width: 42%; }

        .notice {
            margin-top: 20pt; padding: 8pt 10pt; font-size: 8.5pt;
            background: #f6f1e4; border-left: 2pt solid #b5643f; color: #5a4a3a;
            text-align: left;
        }

        h2 {
            font-size: 13pt; color: #4a2c5a; margin: 16pt 0 2pt;
            border-bottom: 0.5pt solid #e3dccd; padding-bottom: 3pt;
            page-break-after: avoid;
        }
        .st { font-size: 8.5pt; color: #b5643f; font-style: italic; margin-bottom: 6pt; }
        p { margin: 0 0 7pt; text-align: justify; }

        .foot {
            font-size: 8pt; color: #8a7f74; text-align: center; margin-top: 18pt;
            border-top: 0.5pt solid #e3dccd; padding-top: 6pt;
        }
    </style>
</head>
<body>

    {{-- Cover --}}
    <div class="cover">
        <h1>{{ $kundali->name }}</h1>
        <div class="sub">Vedic Birth Chart Reading</div>

        <table class="facts">
            <tr><td class="k">Date of birth</td><td>{{ $kundali->birth_date->format('j F Y') }}</td></tr>
            <tr><td class="k">Time of birth</td><td>{{ substr($kundali->birth_time, 0, 5) }} ({{ $kundali->timezone }})</td></tr>
            <tr><td class="k">Place of birth</td><td>{{ $kundali->birth_place }}</td></tr>
            <tr><td class="k">Coordinates</td><td>{{ number_format($kundali->latitude, 5) }}, {{ number_format($kundali->longitude, 5) }}</td></tr>
            <tr><td class="k">Lagna</td><td>{{ $facts['lagna']['sign_name'] }} {{ $facts['lagna']['degree_formatted'] }}</td></tr>
            <tr><td class="k">Janma Rashi</td><td>{{ $facts['moon']['sign_name'] }}</td></tr>
            <tr><td class="k">Nakshatra</td><td>{{ $facts['moon']['nakshatra']['name'] }} (pada {{ $facts['moon']['nakshatra']['pada'] }})</td></tr>
            <tr><td class="k">Ayanamsa</td><td>{{ $facts['meta']['ayanamsa_name'] }} {{ $facts['meta']['ayanamsa_formatted'] }}</td></tr>
        </table>

        <div class="notice">
            <strong>About this reading.</strong>
            @if ($ruleMode === 'owner')
                Generated only from the rule set supplied by the owner of this installation.
                Placements with no supplied rule are stated as plain chart facts rather than
                interpreted.
            @else
                Planetary positions are computed with the Swiss Ephemeris using the
                {{ $facts['meta']['ayanamsa_name'] }} ayanamsa and whole-sign bhavas.
            @endif
            Any passage touching health describes tendencies indicated by the chart. It is
            not medical advice and cannot diagnose anything.
        </div>

        <div style="font-size:8pt;color:#8a7f74;margin-top:14pt;">
            Generated {{ now()->format('j F Y') }}
        </div>
    </div>

    <div style="page-break-after: always;"></div>

    {{-- Reading --}}
    @foreach ($sections as $section)
        <h2>{{ $section['title'] }}</h2>
        @if (! empty($section['subtitle']))
            <div class="st">{{ $section['subtitle'] }}</div>
        @endif
        @foreach ($section['paragraphs'] as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    @endforeach

    <div class="foot">
        Positions by Swiss Ephemeris &middot; {{ $facts['meta']['ayanamsa_name'] }} ayanamsa &middot; {{ config('app.name') }}
    </div>
</body>
</html>
