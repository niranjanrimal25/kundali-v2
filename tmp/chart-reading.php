<?php

/**
 * Generates a reading for a chart supplied as house placements rather
 * than birth data, by feeding the real ChartCalculator a stub ephemeris.
 *
 * Everything downstream — dignity, drishti, digbala, conjunctions,
 * navamsa — is computed by the production code, not by hand.
 */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Astrology\ChartCalculator;
use App\Services\Astrology\DoshaDetector;
use App\Services\Astrology\Ephemeris\EphemerisInterface;
use App\Services\Astrology\Interpretation\ReadingGenerator;
use App\Services\Astrology\Support\Zodiac;
use App\Services\Astrology\TimeResolver;
use App\Services\Astrology\VimshottariDasha;
use App\Services\Astrology\YogaDetector;
use Illuminate\Contracts\Console\Kernel;

// ---- The chart as drawn -------------------------------------------------
// Lagna Leo (sign index 4). Signs are 0-based: 0 Aries .. 11 Pisces.
$lagnaSign = 4;                 // Leo
$placements = [                 // graha => sign index
    'Mars' => 4,             // Leo        - house 1
    'Mercury' => 6,             // Libra      - house 3   (combust)
    'Venus' => 6,             // Libra      - house 3
    'Rahu' => 6,             // Libra      - house 3
    'Sun' => 7,             // Scorpio    - house 4
    'Jupiter' => 7,             // Scorpio    - house 4   (combust)
    'Saturn' => 10,            // Aquarius   - house 7
    'Ketu' => 0,             // Aries      - house 9
    'Moon' => 3,             // Cancer     - house 12
];

// No degrees are given on the chart, so each graha is placed mid-sign.
// Mercury and Jupiter are marked combust, so they are nudged close to
// the Sun to reproduce that; nothing else depends on exact degrees.
$longitudes = [];
foreach ($placements as $graha => $sign) {
    $longitudes[$graha] = $sign * 30 + 15.0;
}
// Mercury is marked combust but sits in Libra, one sign BEHIND the Sun.
// So the Sun is placed early in Scorpio and Mercury late in Libra, which
// keeps both in the houses drawn while putting Mercury inside the orb.
$longitudes['Sun'] = 7 * 30 + 3.0;        // Scorpio 3
$longitudes['Mercury'] = 6 * 30 + 27.0;   // Libra 27  -> 6 deg from the Sun
$longitudes['Jupiter'] = 7 * 30 + 8.0;    // Scorpio 8 -> 5 deg from the Sun
$longitudes['Venus'] = 6 * 30 + 20.0;     // Libra 20  -> outside the orb

$stub = new class($longitudes, $lagnaSign) implements EphemerisInterface
{
    public function __construct(private array $longitudes, private int $lagnaSign) {}

    public function calculate(DateTimeImmutable $utc, float $latitude, float $longitude): array
    {
        $planets = [];

        foreach (Zodiac::PLANETS as $name) {
            $lon = $this->longitudes[$name] ?? 0.0;
            $node = in_array($name, ['Rahu', 'Ketu'], true);

            $planets[$name] = [
                'longitude' => $lon,
                'speed' => $node ? -0.05 : 1.0,
                'retrograde' => $node,       // nodes are always retrograde
            ];
        }

        return [
            'planets' => $planets,
            'ascendant' => $this->lagnaSign * 30 + 15.0,
            'midheaven' => 0.0,
            'houses' => [],
            'ayanamsa' => 24.0,
            'julian_day' => 2451545.0,
        ];
    }
};

app()->instance(EphemerisInterface::class, $stub);
app()->forgetInstance(ChartCalculator::class);

$calculator = new ChartCalculator(
    $stub,
    app(TimeResolver::class),
    app(VimshottariDasha::class),
    app(YogaDetector::class),
    app(DoshaDetector::class),
);

$facts = $calculator->calculate('2000-01-01', '12:00', 'UTC', 27.7, 85.3);

echo "LAGNA: {$facts['lagna']['sign_name']}\n";
foreach ($facts['planets'] as $n => $p) {
    printf("  %-8s H%-2d %-12s %-13s%s\n", $p['sanskrit'], $p['house'], $p['sign_name'], $p['dignity'], $p['combust'] ? ' (combust)' : '');
}
echo "\n";

// Simple analysis view
$simple = app(App\Services\Astrology\Interpretation\SimpleAnalysisGenerator::class)->generate($facts);

echo "### 1. Chart Placement Overview\n\n";
foreach ($simple['placements'] as $p) {
    $g = implode(', ', array_map(fn ($x) => $x['sanskrit'].($x['combust'] ? '*' : ''), $p['grahas']));
    printf("- %s%s House: %s (%s - %d) with %s\n",
        $p['is_lagna'] ? 'Ascendant / Lagna - ' : '', $p['ordinal'],
        $p['sign_name'], $p['sign_sanskrit'], $p['sign_number'], $g);
}
echo "\n### 2. Detailed Analysis Based On Your Rules\n\n";
foreach ($simple['analysis'] as $g) {
    echo "**{$g['letter']}. {$g['title']}**\n\n";
    echo "- **{$g['heading']}:**\n";
    foreach ($g['points'] as $pt) { echo "  - $pt\n"; }
    echo "\n";
}
echo "### 3. Summary of Key Outcomes\n\n";
foreach ($simple['summary'] as $i => $s) {
    echo ($i + 1).". **{$s['label']}**\n";
    foreach ($s['points'] as $pt) { echo "   - $pt\n"; }
    echo "\n";
}
exit;

$sections = app(ReadingGenerator::class)->generate($facts);

foreach ($sections as $s) {
    if ($s['key'] === 'dasha') {
        continue;
    }   // needs a real birth moment
    echo '## '.$s['title']."\n";
    if (! empty($s['subtitle'])) {
        echo '_'.$s['subtitle']."_\n";
    }
    echo "\n";
    foreach ($s['paragraphs'] as $p) {
        echo $p."\n\n";
    }
}
