<?php

namespace Tests\Feature;

use App\Services\Astrology\ChartCalculator;
use App\Services\Astrology\DoshaDetector;
use App\Services\Astrology\Ephemeris\EphemerisInterface;
use App\Services\Astrology\Reading\ChartPayload;
use App\Services\Astrology\Reading\RuleBase;
use App\Services\Astrology\Reading\RuleEngine;
use App\Services\Astrology\Support\Zodiac;
use App\Services\Astrology\TimeResolver;
use App\Services\Astrology\VimshottariDasha;
use App\Services\Astrology\YogaDetector;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * "Mars sitting in the 6th house does not mean a person will always
 * suffer injuries. Only when Mars in the 6th forms a conjunction with
 * malefic planets does it trigger negative influences."
 *
 * This is the rule the whole Trik matrix hangs on, so it is pinned.
 */
class AfflictionGateTest extends TestCase
{
    use RefreshDatabase;

    /** Build a chart from explicit sign placements. */
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
                    'midheaven' => 0.0,
                    'houses' => [],
                    'ayanamsa' => 24.0,
                    'julian_day' => 2451545.0,
                ];
            }
        };

        $calculator = new ChartCalculator(
            $stub,
            app(TimeResolver::class),
            app(VimshottariDasha::class),
            app(YogaDetector::class),
            app(DoshaDetector::class),
        );

        return ChartPayload::fromFacts(
            $calculator->calculate('2000-01-01', '12:00', 'UTC', 27.7, 85.3)
        );
    }

    /** Gemini lagna puts Scorpio, Mars's own sign, on the 6th. */
    private function geminiChart(array $overrides = []): array
    {
        return array_merge([
            'Sun' => 2, 'Moon' => 3, 'Mars' => 7, 'Mercury' => 2,
            'Jupiter' => 4, 'Venus' => 1, 'Saturn' => 9, 'Rahu' => 10, 'Ketu' => 4,
        ], $overrides);
    }

    private function trikHits(array $payload, string $graha): int
    {
        $hits = 0;

        foreach ((new RuleEngine)->evaluate((new RuleBase)->rules(), $payload) as $rule) {
            if (str_starts_with($rule['id'], 'TRIK_'.strtoupper($graha))) {
                $hits++;
            }
        }

        return $hits;
    }

    #[Test]
    public function an_unafflicted_graha_in_a_trik_house_triggers_nothing(): void
    {
        $payload = $this->payload($this->geminiChart(), 2);
        $mars = $payload['planet']['Mars'];

        $this->assertSame(6, $mars['house']);
        $this->assertSame('own', $mars['dignity']);
        $this->assertFalse($mars['withMalefic']);
        $this->assertFalse($mars['afflicted']);

        $this->assertSame(0, $this->trikHits($payload, 'Mars'),
            'Mars in the 6th, unafflicted, must not predict injury');
    }

    #[Test]
    public function the_same_placement_with_a_malefic_does_trigger(): void
    {
        // Identical chart, except Saturn joins Mars in the 6th.
        $payload = $this->payload($this->geminiChart(['Saturn' => 7]), 2);
        $mars = $payload['planet']['Mars'];

        $this->assertSame(6, $mars['house']);
        $this->assertTrue($mars['withMalefic']);
        $this->assertTrue($mars['afflicted']);

        $this->assertSame(3, $this->trikHits($payload, 'Mars'),
            'Malefic conjunction must trigger all three Trik facets');
    }

    #[Test]
    public function an_enemy_sign_alone_also_counts_as_affliction(): void
    {
        // Aries lagna puts Virgo, an enemy sign for Mars, on the 6th.
        $payload = $this->payload([
            'Sun' => 0, 'Moon' => 1, 'Mars' => 5, 'Mercury' => 2,
            'Jupiter' => 3, 'Venus' => 4, 'Saturn' => 9, 'Rahu' => 10, 'Ketu' => 4,
        ], 0);

        $mars = $payload['planet']['Mars'];

        $this->assertSame(6, $mars['house']);
        $this->assertTrue($mars['inEnemySign']);
        $this->assertFalse($mars['withMalefic']);
        $this->assertTrue($mars['afflicted'], 'An enemy sign is an affliction in its own right');
    }

    #[Test]
    public function every_trik_rule_carries_the_affliction_gate(): void
    {
        foreach ((new RuleBase)->load('trik') as $rule) {
            $facts = array_column($rule['when']['all'], 'fact');

            $this->assertContains(
                "planet.{$rule['subject']}.afflicted",
                $facts,
                "{$rule['id']} is missing the affliction gate and would fire on placement alone"
            );
        }
    }

    #[Test]
    public function lacking_directional_strength_is_not_described_as_inauspicious(): void
    {
        $rules = collect((new RuleBase)->load('composite'))->keyBy('id');

        foreach (['SUN_WEAK_4TH', 'MARS_DIGHEENA_4TH'] as $id) {
            $text = $rules[$id]['then']['text'];

            $this->assertStringContainsString('reduced', $text);
            $this->assertMatchesRegularExpression('/not make it inauspicious|rather than turned harmful/i', $text);
        }
    }
}
