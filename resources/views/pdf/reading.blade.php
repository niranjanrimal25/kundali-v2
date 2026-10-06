<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $kundali->name }} — Kundali Reading</title>
    <style>
        /* mPDF selects a Devanagari-capable font per text run via
           autoScriptToLang, so no @font-face is needed here. Avoid
           italics on body text: the italic face has no Devanagari
           coverage and silently falls back to boxes. */
        body { font-family: sans-serif; font-size: 10.5pt; line-height: 1.6; color: #2b2520; }

        h1 { font-size: 22pt; color: #4a2c5a; margin: 0 0 4pt; }
        h2 { font-size: 13pt; color: #4a2c5a; margin: 14pt 0 3pt;
             border-bottom: 0.5pt solid #e3dccd; padding-bottom: 2pt; }

        .sub { font-size: 10pt; color: #7b3f61; margin-bottom: 14pt; }
        .st  { font-size: 9pt; color: #b5643f; margin-bottom: 4pt; }
        p    { margin: 0 0 5pt; }

        .facts { width: 100%; border-collapse: collapse; margin-bottom: 10pt; }
        .facts td { padding: 3pt 6pt; font-size: 9.5pt; border-bottom: 0.4pt solid #e3dccd; }
        .facts td.k { color: #7b3f61; width: 38%; }

        .notice { padding: 6pt 8pt; font-size: 8.5pt; background: #f6f1e4;
                  border-left: 2pt solid #b5643f; color: #5a4a3a; }
        .focus  { margin: 2pt 0; padding: 3pt 6pt; border-left: 2pt solid #b5643f; background: #fdf3ec; }
        .focus b { color: #b5643f; }
        .lagnesh { padding: 5pt 8pt; border-left: 2pt solid #4a2c5a; background: #f6f2f8; margin-bottom: 8pt; }
        .foot { font-size: 8pt; color: #8a7f74; text-align: center; margin-top: 14pt;
                border-top: 0.4pt solid #e3dccd; padding-top: 5pt; }
    </style>
</head>
<body>

<h1>{{ $kundali->name }}</h1>
<div class="sub">Vedic Birth Chart Reading</div>

<table class="facts">
    <tr><td class="k">Date of birth</td><td>{{ $kundali->birth_date->format('j F Y') }}</td></tr>
    <tr><td class="k">Time of birth</td><td>{{ substr($kundali->birth_time, 0, 5) }} ({{ $kundali->timezone }})</td></tr>
    <tr><td class="k">Place of birth</td><td>{{ $kundali->birth_place }}</td></tr>
    <tr><td class="k">Lagna</td><td>{{ $facts['lagna']['sign_name'] }} {{ $facts['lagna']['degree_formatted'] }}</td></tr>
    <tr><td class="k">Janma Rashi</td><td>{{ $facts['moon']['sign_name'] }}</td></tr>
    <tr><td class="k">Nakshatra</td><td>{{ $facts['moon']['nakshatra']['name'] }} (pada {{ $facts['moon']['nakshatra']['pada'] }})</td></tr>
    <tr><td class="k">Ayanamsa</td><td>{{ $facts['meta']['ayanamsa_name'] }} {{ $facts['meta']['ayanamsa_formatted'] }}</td></tr>
</table>

<div class="notice">
    <b>About this reading.</b>
    Positions are computed with the Swiss Ephemeris using the
    {{ $facts['meta']['ayanamsa_name'] }} ayanamsa and whole-sign bhavas.
    Findings come from the supplied rule base. Any passage touching health describes
    tendencies indicated by the chart. It is not medical advice and cannot diagnose anything.
</div>

{{-- Lagna and its lord, established first --}}
@if (! empty($report['lagnesh']))
    @php($lg = $report['lagnesh'])
    <h2>Lagna and its Lord</h2>
    <div class="lagnesh">
        <p><b>Ascendant:</b> {{ $lg['lagna']['signName'] }}
            ({{ $lg['lagna']['signSanskrit'] }} &mdash; {{ $lg['lagna']['signNumber'] }})
            &nbsp;&nbsp; <b>Lagnesh:</b> {{ $lg['lagnesh']['sanskrit'] }},
            {{ $lg['lagnesh']['dignity'] }} in {{ $lg['lagnesh']['signName'] }}
            &nbsp;&nbsp; <b>{{ $lg['afflicted'] ? 'Afflicted' : 'Not afflicted' }}</b></p>
        <p>{{ $lg['verdict'] }}</p>
        @if ($lg['qualities'])<p><b>Core qualities:</b> {{ $lg['qualities'] }}</p>@endif
        @if ($lg['health'])<p><b>Bodily vulnerability:</b> {{ $lg['health'] }}</p>@endif
    </div>
@endif

<h2>{{ $report['labels']['s1'] ?? '1. Chart Placement Overview' }}</h2>
@foreach ($report['placements'] as $p)
    <p>
        <b>@if ($p['isLagna']){{ $report['labels']['lagna'] }} &mdash; @endif{{ $p['ordinal'] }} {{ $report['labels']['house'] }}:</b>
        {{ $p['signName'] }} ({{ $p['signSanskrit'] }} &mdash; {{ $p['signNumber'] }}) {{ $report['labels']['with'] }}
        @foreach ($p['grahas'] as $g){{ $g['sanskrit'] }}@if ($g['combust'])*@endif{{ ! $loop->last ? ', ' : '' }}@endforeach
    </p>
@endforeach

<h2>{{ $report['labels']['s2'] ?? '2. Detailed Analysis Based On Your Rules' }}</h2>
@foreach ($report['groups'] as $group)
    <p><b>{{ $group['letter'] }}. {{ $group['title'] }}</b></p>
    <div class="st">{{ $group['heading'] }}</div>

    @if (! empty($group['block']))
        @foreach ([
            'health' => 'Primary Health Focus',
            'mind' => 'Mind &amp; Temperament',
            'people' => 'Key Relationships &amp; Dynamics',
        ] as $k => $lbl)
            @if (! empty($group['block'][$k]))
                <div class="focus"><b>{!! $lbl !!}:</b> {{ $group['block'][$k] }}</div>
            @endif
        @endforeach

        @foreach ($group['block']['notes'] as $note)
            <p style="font-size:9pt;color:#2f6f5e;">{{ $note }}</p>
        @endforeach
    @endif

    @foreach ($group['points'] as $point)
        <p>&bull; {{ $point['text'] }}</p>
    @endforeach

    @if ($group['points'] === [] && empty($group['block']))
        <p>{{ $report['labels']['none'] }}</p>
    @endif
@endforeach

@if ($report['summary'] !== [])
    <h2>{{ $report['labels']['s3'] ?? '3. Summary of Key Outcomes' }}</h2>
    @foreach ($report['summary'] as $i => $bucket)
        <p><b>{{ $i + 1 }}. {{ $bucket['label'] }}</b></p>
        @foreach ($bucket['points'] as $point)
            <p>&bull; {{ $point }}</p>
        @endforeach
    @endforeach
@endif

{{-- Dasha, yogas, doshas: restored sections --}}
@if (! empty($report['dasha']))
    <h2>4. Current Planetary Period</h2>
    @foreach (['mahadasha' => 'Mahadasha', 'antardasha' => 'Antardasha'] as $k => $lbl)
        @if (! empty($report['dasha'][$k]))
            @php($d = $report['dasha'][$k])
            <p><b>{{ $lbl }}:</b> {{ $d['sanskrit'] }}, {{ $d['start'] }} to {{ $d['end'] }}. {{ $d['standing'] }}</p>
        @endif
    @endforeach
@endif

@if (! empty($report['yogas']))
    <h2>5. Yogas</h2>
    <p>{{ $report['yogas']['note'] }}</p>
    @foreach ($report['yogas']['items'] as $y)
        <p>&bull; <b>{{ $y['name'] }}</b> ({{ $y['strength'] }}). {{ $y['basis'] }}</p>
    @endforeach
@endif

@if (! empty($report['doshas']))
    <h2>6. Doshas and Transits</h2>
    @foreach ($report['doshas']['items'] as $d)
        <p>&bull; <b>{{ $d['name'] }}.</b> {{ $d['basis'] }}@if ($d['cancelled']) {{ $d['cancellation'] }} The dosha is therefore cancelled.@endif</p>
    @endforeach
    @if ($report['doshas']['transit'])
        <p>&bull; <b>{{ $report['doshas']['transit']['name'] }}.</b> {{ $report['doshas']['transit']['basis'] }}</p>
    @endif
@endif

<div class="foot">
    Positions by Swiss Ephemeris &middot; {{ $facts['meta']['ayanamsa_name'] }} ayanamsa &middot; {{ config('app.name') }}
</div>

</body>
</html>
