<?php

namespace Tests\Feature;

use App\Services\Astrology\ChartCalculator;
use App\Services\Astrology\DoshaDetector;
use App\Services\Astrology\Ephemeris\EphemerisInterface;
use App\Services\Astrology\Reading\ChartPayload;
use App\Services\Astrology\Reading\IntersectionEngine;
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
 * The filtering engine that replaced the Cartesian derivation.
 * Pinned against the owner's worked example: Mercury, Venus and Rahu
 * in the 3rd in Libra must report nerves, skin and hands, and must NOT
 * report kidneys, uterus or the digestive tract.
 */
class IntersectionEngineTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $signs, int $lagnaSign): array
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
                    'planets' => $planets,
                    'ascendant' => $this->lagna * 30 + 15.0,
                    'midheaven' => 0.0, 'houses' => [],
                    'ayanamsa' => 24.0, 'julian_day' => 2451545.0,
                ];
            }
        };

        $calculator = new ChartCalculator(
            $stub, app(TimeResolver::class), app(VimshottariDasha::class),
            app(YogaDetector::class), app(DoshaDetector::class),
        );

        return ChartPayload::fromFacts($calculator->calculate('2000-01-01', '12:00', 'UTC', 27.7, 85.3));
    }

    /** Leo lagna: Mercury, Venus and Rahu in Libra, the 3rd. */
    private function leoChart(): array
    {
        return $this->payload([
            'Sun' => 7, 'Moon' => 3, 'Mars' => 4, 'Mercury' => 6,
            'Jupiter' => 7, 'Venus' => 6, 'Saturn' => 10, 'Rahu' => 6, 'Ketu' => 0,
        ], 4);
    }

    private function blocks(): array
    {
        return (new IntersectionEngine(new RuleBase))->forHouses($this->leoChart());
    }

    #[Test]
    public function it_keeps_only_attributes_the_bhava_actually_governs(): void
    {
        $health = $this->blocks()[3]['health'];

        // The 3rd governs hands, arms, nerves, respiration and skin.
        $this->assertStringContainsString('nervous system', $health);
        $this->assertStringContainsString('skin', $health);
        $this->assertStringContainsString('hands', $health);
    }

    #[Test]
    public function it_drops_attributes_the_bhava_does_not_govern(): void
    {
        $health = $this->blocks()[3]['health'];

        // Venus rules the kidneys and Mercury the digestive tract, but
        // neither belongs to the 3rd. This is the kitchen-sink fix.
        foreach (['kidney', 'urinary', 'reproductive', 'uterus', 'digestive', 'intestine'] as $organ) {
            $this->assertStringNotContainsStringIgnoringCase(
                $organ, $health, "The 3rd bhava must not report {$organ}"
            );
        }
    }

    #[Test]
    public function a_strong_graha_suppresses_severe_warnings(): void
    {
        $block = $this->blocks()[3];

        // Venus is in Libra, its own sign, so it protects the house.
        $this->assertNotEmpty($block['notes']);
        $this->assertStringContainsString('Shukra is strong', implode(' ', $block['notes']));
    }

    #[Test]
    public function each_concept_is_reported_once_however_many_grahas_name_it(): void
    {
        $health = $this->blocks()[3]['health'];

        // Mercury, Venus and Rahu all signify nerves.
        $this->assertSame(1, substr_count(strtolower($health), 'nervous system'));
    }

    #[Test]
    public function three_grahas_in_one_bhava_produce_one_consolidated_block(): void
    {
        $blocks = $this->blocks();

        $this->assertArrayHasKey(3, $blocks);
        $this->assertArrayHasKey('health', $blocks[3]);
        $this->assertArrayHasKey('mind', $blocks[3]);
        $this->assertArrayHasKey('people', $blocks[3]);
    }

    #[Test]
    public function every_house_in_the_taxonomy_is_defined(): void
    {
        $taxonomy = (new RuleBase)->load('taxonomy');

        foreach (range(1, 12) as $house) {
            $this->assertArrayHasKey((string) $house, $taxonomy['houseDomains']);
        }

        foreach (ChartPayload::GRAHAS as $graha) {
            $this->assertArrayHasKey($graha, $taxonomy['grahaAttributes'], "{$graha} has no attributes");
        }
    }
}
