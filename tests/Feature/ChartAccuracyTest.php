<?php

namespace Tests\Feature;

use App\Models\Kundali;
use App\Models\User;
use App\Services\Astrology\KundaliService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the first acceptance criterion: the chart must be
 * astronomically accurate. Runs the jyotish:validate command, which
 * checks our output against the published Lahiri ayanamsa and against
 * an independent swetest invocation.
 */
class ChartAccuracyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_chart_engine_passes_external_validation(): void
    {
        $user = User::factory()->create();

        $kundali = Kundali::create([
            'user_id' => $user->id,
            'name' => 'Accuracy Reference',
            'birth_date' => '1990-05-15',
            'birth_time' => '10:30:00',
            'birth_place' => 'Pokhara, Nepal',
            'latitude' => 28.26689,
            'longitude' => 83.96851,
            'timezone' => 'Asia/Kathmandu',
        ]);

        $this->artisan('jyotish:validate', ['kundali' => $kundali->id])
            ->assertExitCode(0);
    }

    #[Test]
    public function the_golden_chart_still_produces_its_known_values(): void
    {
        $user = User::factory()->create();

        $kundali = Kundali::create([
            'user_id' => $user->id,
            'name' => 'Golden',
            'birth_date' => '1990-05-15',
            'birth_time' => '10:30:00',
            'birth_place' => 'Pokhara, Nepal',
            'latitude' => 28.26689,
            'longitude' => 83.96851,
            'timezone' => 'Asia/Kathmandu',
        ]);

        $facts = app(KundaliService::class)->facts($kundali, true);

        // Regression vector recorded when the engine was first verified.
        $this->assertEqualsWithDelta(23.725791, $facts['meta']['ayanamsa_value'], 0.000_01);
        $this->assertEqualsWithDelta(102.302230, $facts['lagna']['longitude'], 0.000_01);
        $this->assertSame(3, $facts['lagna']['sign'], 'Lagna must be Cancer');
        $this->assertSame('Uttara Ashadha', $facts['moon']['nakshatra']['name']);
        $this->assertSame(1, $facts['moon']['nakshatra']['pada']);

        // Saturn: own sign, retrograde, full digbala in the 7th.
        $saturn = $facts['planets']['Saturn'];
        $this->assertSame(7, $saturn['house']);
        $this->assertSame('own', $saturn['dignity']);
        $this->assertTrue($saturn['retrograde']);
        $this->assertEqualsWithDelta(1.0, $saturn['digbala']['strength'], 0.001);

        // Venus exalted in Pisces.
        $this->assertSame('exalted', $facts['planets']['Venus']['dignity']);
    }
}
