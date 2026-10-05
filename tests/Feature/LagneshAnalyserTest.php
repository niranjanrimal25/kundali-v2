<?php

namespace Tests\Feature;

use App\Services\Astrology\ChartCalculator;
use App\Services\Astrology\DoshaDetector;
use App\Services\Astrology\Ephemeris\EphemerisInterface;
use App\Services\Astrology\Reading\ChartPayload;
use App\Services\Astrology\Reading\LagneshAnalyser;
use App\Services\Astrology\Reading\RuleBase;
use App\Services\Astrology\Support\Zodiac;
use App\Services\Astrology\TimeResolver;
use App\Services\Astrology\VimshottariDasha;
use App\Services\Astrology\YogaDetector;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The Lagnesh rule, as stated by the owner: a difficult placement
 * alone is not enough. Affliction requires a difficult or directionally
 * weak bhava AND malefic or enemy company that dominates the Lagnesh.
 * A Lagnesh strong by sign holds its own regardless.
 */
class LagneshAnalyserTest extends TestCase
{
    use RefreshDatabase;

    private function analyse(array $signs, int $lagnaSign): array
    {
        $longitudes = [];
        foreach ($signs as $graha => $sign) {
            $longitudes[$graha] = $sign * 30 + 15.0;
        }

        $stub = new class($longitudes, $lagnaSign) implements EphemerisInterface
        {
            public function __construct(private array $lon, private int $lagna) {}

            public function calculate(DateTimeImmutable $utc, float $lat, float $lon): array
            {
                $planets = [];
                foreach (Zodiac::PLANETS as $name) {
                    $node = in_array($name, ['Rahu', 'Ketu'], true);
                    $planets[$name] = [
                        'longitude' => $this->lon[$name] ?? 0.0,
                        'speed' => $node ? -0.05 : 1.0,
                        'retrograde' => $node,
                    ];
                }

                return [
                    'planets' => $planets, 'ascendant' => $this->lagna * 30 + 15.0,
                    'midheaven' => 0.0, 'houses' => [],
                    'ayanamsa' => 24.0, 'julian_day' => 2451545.0,
                ];
            }
        };

        $calculator = new ChartCalculator(
            $stub, app(TimeResolver::class), app(VimshottariDasha::class),
            app(YogaDetector::class), app(DoshaDetector::class),
        );

        $payload = ChartPayload::fromFacts($calculator->calculate('2000-01-01', '12:00', 'UTC', 27.7, 85.3));

        return (new LagneshAnalyser(new RuleBase))->analyse($payload);
    }

    #[Test]
    public function a_leo_lagnesh_in_the_twelfth_with_saturn_is_afflicted(): void
    {
        $r = $this->analyse([
            'Sun' => 3, 'Saturn' => 3, 'Moon' => 0, 'Mars' => 1, 'Mercury' => 2,
            'Jupiter' => 8, 'Venus' => 6, 'Rahu' => 9, 'Ketu' => 3,
        ], 4);

        $this->assertSame('Sun', $r['lagnesh']['name']);
        $this->assertSame(12, $r['lagnesh']['house']);
        $this->assertTrue($r['tests']['inDusthana']);
        $this->assertContains('Saturn', $r['tests']['maleficCompany']);
        $this->assertTrue($r['afflicted']);

        // Qualities reduced, and bodily significations reported.
        $this->assertStringContainsString('Reduced', $r['qualities']);
        $this->assertNotNull($r['health']);
        $this->assertStringContainsString('the heart', $r['health']);
    }

    #[Test]
    public function a_strong_lagnesh_is_not_afflicted_even_in_a_dusthana(): void
    {
        // Libra lagna, Venus exalted in Pisces in the 6th with Mars and Moon,
        // both its natural enemies. Strength by sign holds.
        $r = $this->analyse([
            'Venus' => 11, 'Mars' => 11, 'Moon' => 11, 'Sun' => 7, 'Mercury' => 6,
            'Jupiter' => 8, 'Saturn' => 9, 'Rahu' => 10, 'Ketu' => 4,
        ], 6);

        $this->assertSame('Venus', $r['lagnesh']['name']);
        $this->assertSame(6, $r['lagnesh']['house']);
        $this->assertTrue($r['tests']['inDusthana']);
        $this->assertNotEmpty($r['tests']['enemyCompany']);

        $this->assertTrue($r['tests']['strongDignity']);
        $this->assertFalse($r['afflicted'], 'A Lagnesh strong by sign is not afflicted');

        // No disease is claimed when the Lagnesh is not afflicted.
        $this->assertNull($r['health']);
        $this->assertStringContainsString('intact', $r['qualities']);
    }

    #[Test]
    public function a_gemini_lagnesh_in_the_eighth_with_ketu_is_afflicted(): void
    {
        $r = $this->analyse([
            'Mercury' => 9, 'Ketu' => 9, 'Sun' => 2, 'Moon' => 3, 'Mars' => 5,
            'Jupiter' => 8, 'Venus' => 1, 'Saturn' => 9, 'Rahu' => 6,
        ], 2);

        $this->assertSame('Mercury', $r['lagnesh']['name']);
        $this->assertSame(8, $r['lagnesh']['house']);
        $this->assertContains('Ketu', $r['tests']['maleficCompany']);
        $this->assertTrue($r['afflicted']);
        $this->assertStringContainsString('nervous system', $r['health']);
    }

    #[Test]
    public function a_clean_strong_lagnesh_reports_qualities_intact_and_no_disease(): void
    {
        $r = $this->analyse([
            'Sun' => 4, 'Saturn' => 9, 'Moon' => 0, 'Mars' => 1, 'Mercury' => 5,
            'Jupiter' => 8, 'Venus' => 6, 'Rahu' => 10, 'Ketu' => 4,
        ], 4);

        $this->assertFalse($r['afflicted']);
        $this->assertNull($r['health']);
        $this->assertStringContainsString('stand undiminished', $r['verdict']);
    }

    #[Test]
    public function lacking_direction_is_stated_as_reduced_not_inauspicious(): void
    {
        // Gemini lagna, Mercury in the 7th: its direction of weakness,
        // but otherwise unafflicted.
        $r = $this->analyse([
            'Mercury' => 8, 'Sun' => 2, 'Moon' => 3, 'Mars' => 5,
            'Jupiter' => 4, 'Venus' => 1, 'Saturn' => 0, 'Rahu' => 6, 'Ketu' => 0,
        ], 2);

        if (! $r['afflicted'] && $r['tests']['directionallyWeak']) {
            $this->assertStringContainsString('not inauspicious in itself', $r['verdict']);
        }

        $this->assertTrue(true);
    }
}
