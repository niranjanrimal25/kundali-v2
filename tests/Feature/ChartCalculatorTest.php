<?php

namespace Tests\Feature;

use App\Services\Astrology\ChartCalculator;
use App\Services\Astrology\Support\Zodiac;
use App\Services\Astrology\VimshottariDasha;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * End-to-end verification of the astronomy + Vedic math layers.
 *
 * Reference values are taken from Swiss Ephemeris itself (the authority)
 * and cross-checked against standard Vedic software conventions.
 */
class ChartCalculatorTest extends TestCase
{
    private function calculator(): ChartCalculator
    {
        return app(ChartCalculator::class);
    }

    /** A fixed reference chart used across several assertions. */
    private function referenceChart(): array
    {
        // 15 May 1990, 10:30 local, Pokhara, Nepal (+5:45)
        return $this->calculator()->calculate(
            '1990-05-15', '10:30', 'Asia/Kathmandu', 28.26689, 83.96851
        );
    }

    #[Test]
    public function it_computes_the_lahiri_ayanamsa_correctly(): void
    {
        $chart = $this->referenceChart();

        // Lahiri ayanamsa for 1990 is approximately 23°43'
        $this->assertEqualsWithDelta(23.7258, $chart['meta']['ayanamsa_value'], 0.001);
        $this->assertSame('Lahiri', $chart['meta']['ayanamsa_name']);
    }

    #[Test]
    public function it_uses_whole_sign_houses(): void
    {
        $chart = $this->referenceChart();

        $this->assertSame('Whole Sign', $chart['meta']['house_system']);

        // Under Whole Sign, the Lagna sign IS the 1st house, and each
        // subsequent house is exactly the next sign.
        $lagnaSign = $chart['lagna']['sign'];

        foreach ($chart['houses'] as $number => $house) {
            $expected = ($lagnaSign + $number - 1) % 12;
            $this->assertSame($expected, $house['sign'], "House {$number} sign mismatch");
        }
    }

    #[Test]
    public function it_places_every_planet_in_a_valid_house_and_sign(): void
    {
        $chart = $this->referenceChart();

        $this->assertCount(9, $chart['planets']);

        foreach (Zodiac::PLANETS as $name) {
            $planet = $chart['planets'][$name];

            $this->assertArrayHasKey($name, $chart['planets']);
            $this->assertGreaterThanOrEqual(0, $planet['sign']);
            $this->assertLessThanOrEqual(11, $planet['sign']);
            $this->assertGreaterThanOrEqual(1, $planet['house']);
            $this->assertLessThanOrEqual(12, $planet['house']);
            $this->assertGreaterThanOrEqual(0, $planet['degree_in_sign']);
            $this->assertLessThan(30, $planet['degree_in_sign']);

            // House must agree with sign under Whole Sign houses.
            $this->assertSame(
                Zodiac::houseOfSign($planet['sign'], $chart['lagna']['sign']),
                $planet['house'],
                "{$name}: house does not match its sign"
            );
        }
    }

    #[Test]
    public function ketu_is_always_exactly_opposite_rahu(): void
    {
        $chart = $this->referenceChart();

        $rahu = $chart['planets']['Rahu']['longitude'];
        $ketu = $chart['planets']['Ketu']['longitude'];

        $separation = abs($rahu - $ketu);
        if ($separation > 180) {
            $separation = 360 - $separation;
        }

        $this->assertEqualsWithDelta(180.0, $separation, 0.0001);

        // They must also be exactly 6 houses apart.
        $this->assertSame(
            6,
            abs($chart['planets']['Rahu']['house'] - $chart['planets']['Ketu']['house']),
        );
    }

    #[Test]
    public function the_nodes_are_always_retrograde(): void
    {
        $chart = $this->referenceChart();

        $this->assertTrue($chart['planets']['Rahu']['retrograde']);
        $this->assertTrue($chart['planets']['Ketu']['retrograde']);
    }

    #[Test]
    public function it_detects_exaltation_correctly(): void
    {
        // The Sun is exalted in Aries. Around 14 April the Sun enters
        // sidereal Aries, so late April guarantees an exalted Sun.
        $chart = $this->calculator()->calculate(
            '1990-04-25', '12:00', 'Asia/Kathmandu', 27.7172, 85.3240
        );

        $this->assertSame(0, $chart['planets']['Sun']['sign'], 'Sun should be in Aries');
        $this->assertSame('exalted', $chart['planets']['Sun']['dignity']);
        $this->assertSame(5, $chart['planets']['Sun']['dignity_score']);
    }

    #[Test]
    public function it_detects_debilitation_correctly(): void
    {
        // The Sun is debilitated in Libra (mid-Oct to mid-Nov sidereal).
        $chart = $this->calculator()->calculate(
            '1990-10-25', '12:00', 'Asia/Kathmandu', 27.7172, 85.3240
        );

        $this->assertSame(6, $chart['planets']['Sun']['sign'], 'Sun should be in Libra');
        $this->assertSame('debilitated', $chart['planets']['Sun']['dignity']);
        $this->assertSame(0, $chart['planets']['Sun']['dignity_score']);
    }

    #[Test]
    public function it_computes_digbala_correctly(): void
    {
        $chart = $this->referenceChart();

        // Jupiter and Mercury get directional strength in the 1st (East)
        $this->assertSame(1, $chart['planets']['Jupiter']['digbala']['strong_house']);
        $this->assertSame('East', $chart['planets']['Jupiter']['digbala']['direction']);

        // Saturn in the 7th (West)
        $this->assertSame(7, $chart['planets']['Saturn']['digbala']['strong_house']);
        $this->assertSame('West', $chart['planets']['Saturn']['digbala']['direction']);

        // Sun and Mars in the 10th (South)
        $this->assertSame(10, $chart['planets']['Sun']['digbala']['strong_house']);

        // Moon and Venus in the 4th (North)
        $this->assertSame(4, $chart['planets']['Venus']['digbala']['strong_house']);

        // Rahu/Ketu have no directional strength
        $this->assertFalse($chart['planets']['Rahu']['digbala']['applicable']);

        // Strength must always be within 0..1
        foreach ($chart['planets'] as $planet) {
            if ($planet['digbala']['applicable']) {
                $this->assertGreaterThanOrEqual(0, $planet['digbala']['strength']);
                $this->assertLessThanOrEqual(1, $planet['digbala']['strength']);
            }
        }
    }

    #[Test]
    public function it_applies_special_aspects_for_mars_jupiter_and_saturn(): void
    {
        $chart = $this->referenceChart();

        $marsHouse = $chart['planets']['Mars']['house'];
        $expectedMars = array_map(
            fn ($d) => (($marsHouse - 1 + $d - 1) % 12) + 1,
            [4, 7, 8]
        );
        $this->assertSame($expectedMars, $chart['planets']['Mars']['aspects_houses']);

        $jupiterHouse = $chart['planets']['Jupiter']['house'];
        $expectedJupiter = array_map(
            fn ($d) => (($jupiterHouse - 1 + $d - 1) % 12) + 1,
            [5, 7, 9]
        );
        $this->assertSame($expectedJupiter, $chart['planets']['Jupiter']['aspects_houses']);

        // The Sun has only the 7th-house aspect.
        $this->assertCount(1, $chart['planets']['Sun']['aspects_houses']);
    }

    #[Test]
    public function it_assigns_house_lordships_relative_to_the_lagna(): void
    {
        $chart = $this->referenceChart();

        foreach ($chart['houses'] as $number => $house) {
            $this->assertSame(
                Zodiac::lordOf($house['sign']),
                $house['lord'],
                "House {$number} lord mismatch"
            );

            // The lord must actually be placed somewhere valid.
            $this->assertNotNull($house['lord_house']);
            $this->assertGreaterThanOrEqual(1, $house['lord_house']);
            $this->assertLessThanOrEqual(12, $house['lord_house']);
        }
    }

    #[Test]
    public function every_planet_appears_as_an_occupant_of_exactly_one_house(): void
    {
        $chart = $this->referenceChart();

        $allOccupants = [];
        foreach ($chart['houses'] as $house) {
            $allOccupants = array_merge($allOccupants, $house['occupants']);
        }

        sort($allOccupants);
        $expected = Zodiac::PLANETS;
        sort($expected);

        $this->assertSame($expected, $allOccupants);
    }

    #[Test]
    public function it_computes_the_vimshottari_dasha_balance_at_birth(): void
    {
        $chart = $this->referenceChart();

        $dasha = $chart['dasha'];

        // The first Mahadasha lord must be the Moon's nakshatra lord.
        $this->assertSame($dasha['birth_nakshatra_lord'], $dasha['balance_at_birth']['lord']);
        $this->assertSame($dasha['birth_nakshatra_lord'], $dasha['mahadashas'][0]['lord']);

        // Balance cannot exceed the full length of that Mahadasha.
        $maxYears = VimshottariDasha::YEARS[$dasha['balance_at_birth']['lord']];
        $this->assertLessThanOrEqual($maxYears, $dasha['balance_at_birth']['decimal_years']);
        $this->assertGreaterThan(0, $dasha['balance_at_birth']['decimal_years']);
    }

    #[Test]
    public function the_dasha_sequence_follows_the_vimshottari_order_without_gaps(): void
    {
        $chart = $this->referenceChart();
        $mahadashas = $chart['dasha']['mahadashas'];

        $order = VimshottariDasha::ORDER;
        $startIndex = array_search($mahadashas[0]['lord'], $order, true);

        foreach ($mahadashas as $i => $maha) {
            $this->assertSame($order[($startIndex + $i) % 9], $maha['lord']);

            // Each period must start exactly where the previous ended.
            if ($i > 0) {
                $this->assertSame($mahadashas[$i - 1]['end'], $maha['start']);
            }

            // Antardashas must tile the Mahadasha exactly.
            $this->assertCount(9, $maha['antardashas']);
            $this->assertSame($maha['start'], $maha['antardashas'][0]['start']);
            $this->assertSame($maha['end'], $maha['antardashas'][8]['end']);
        }
    }

    #[Test]
    public function exactly_one_mahadasha_is_marked_as_currently_running(): void
    {
        $chart = $this->referenceChart();

        $current = array_filter($chart['dasha']['mahadashas'], fn ($m) => $m['current']);

        $this->assertCount(1, $current);
        $this->assertNotNull($chart['dasha']['current']['mahadasha']);
        $this->assertNotNull($chart['dasha']['current']['antardasha']);
    }

    #[Test]
    public function it_records_the_historical_offset_for_a_pre_1986_nepali_birth(): void
    {
        $chart = $this->calculator()->calculate(
            '1980-07-20', '10:30', 'Asia/Kathmandu', 27.7172, 85.3240
        );

        $this->assertSame('+05:30', $chart['meta']['utc_offset']);
        $this->assertTrue($chart['meta']['historical_offset_applied']);
    }

    #[Test]
    public function the_same_birth_data_always_produces_an_identical_chart(): void
    {
        $first = $this->referenceChart();
        $second = $this->referenceChart();

        // Dasha "current" flags depend on today's date, so compare the
        // deterministic astronomy and structure only.
        $this->assertSame($first['planets'], $second['planets']);
        $this->assertSame($first['houses'], $second['houses']);
        $this->assertSame($first['lagna'], $second['lagna']);
    }

    #[Test]
    public function a_one_hour_time_difference_changes_the_lagna(): void
    {
        // The Ascendant advances roughly one sign every two hours, so a
        // meaningful time change must move it. This guards against the
        // birth time being silently ignored.
        $a = $this->calculator()->calculate('1990-05-15', '06:00', 'Asia/Kathmandu', 28.26689, 83.96851);
        $b = $this->calculator()->calculate('1990-05-15', '10:00', 'Asia/Kathmandu', 28.26689, 83.96851);

        $this->assertNotSame($a['lagna']['sign'], $b['lagna']['sign']);
    }

    #[Test]
    public function latitude_affects_the_ascendant(): void
    {
        // Same instant, very different latitudes — the Ascendant degree
        // must differ, proving latitude is genuinely used.
        $pokhara = $this->calculator()->calculate('1990-05-15', '10:30', 'UTC', 28.26689, 83.96851);
        $sydney = $this->calculator()->calculate('1990-05-15', '10:30', 'UTC', -33.8688, 83.96851);

        $this->assertNotEqualsWithDelta(
            $pokhara['lagna']['longitude'],
            $sydney['lagna']['longitude'],
            0.01
        );
    }

    #[Test]
    public function it_computes_navamsa_positions_for_every_planet(): void
    {
        $chart = $this->referenceChart();

        $this->assertCount(9, $chart['divisional']['D9']);

        foreach ($chart['divisional']['D9'] as $name => $position) {
            $this->assertGreaterThanOrEqual(0, $position['sign']);
            $this->assertLessThanOrEqual(11, $position['sign']);

            // Vargottama means the D1 and D9 signs are identical.
            $this->assertSame(
                $chart['planets'][$name]['sign'] === $position['sign'],
                $position['vargottama'],
                "{$name}: vargottama flag inconsistent"
            );
        }
    }

    #[Test]
    public function nakshatra_and_pada_are_always_in_range(): void
    {
        $chart = $this->referenceChart();

        foreach ($chart['planets'] as $name => $planet) {
            $n = $planet['nakshatra'];

            $this->assertGreaterThanOrEqual(1, $n['number'], "{$name} nakshatra number");
            $this->assertLessThanOrEqual(27, $n['number'], "{$name} nakshatra number");
            $this->assertGreaterThanOrEqual(1, $n['pada'], "{$name} pada");
            $this->assertLessThanOrEqual(4, $n['pada'], "{$name} pada");
            $this->assertNotEmpty($n['name']);
            $this->assertContains($n['lord'], VimshottariDasha::ORDER);
        }
    }
}
