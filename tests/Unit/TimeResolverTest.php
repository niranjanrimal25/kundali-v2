<?php

namespace Tests\Unit;

use App\Services\Astrology\TimeResolver;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Timezone handling is the single largest source of wrong Kundalis,
 * so it gets the most rigorous tests in the suite.
 */
class TimeResolverTest extends TestCase
{
    private TimeResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new TimeResolver;
    }

    #[Test]
    public function it_applies_nepals_modern_offset_of_plus_5_45(): void
    {
        $utc = $this->resolver->toUtc('1990-05-15', '10:30', 'Asia/Kathmandu');

        // 10:30 local − 5:45 = 04:45 UTC
        $this->assertSame('1990-05-15 04:45:00', $utc->format('Y-m-d H:i:s'));
        $this->assertSame(5.75, $this->resolver->offsetHours('1990-05-15', '10:30', 'Asia/Kathmandu'));
        $this->assertSame('+05:45', $this->resolver->offsetLabel('1990-05-15', '10:30', 'Asia/Kathmandu'));
    }

    #[Test]
    public function it_applies_nepals_historical_offset_of_plus_5_30_before_1986(): void
    {
        // Nepal moved from +5:30 to +5:45 on 1986-01-01. A birth before
        // that date MUST use +5:30 or the Lagna will be wrong.
        $utc = $this->resolver->toUtc('1980-07-20', '10:30', 'Asia/Kathmandu');

        // 10:30 local − 5:30 = 05:00 UTC
        $this->assertSame('1980-07-20 05:00:00', $utc->format('Y-m-d H:i:s'));
        $this->assertSame(5.5, $this->resolver->offsetHours('1980-07-20', '10:30', 'Asia/Kathmandu'));
        $this->assertSame('+05:30', $this->resolver->offsetLabel('1980-07-20', '10:30', 'Asia/Kathmandu'));
    }

    #[Test]
    public function it_flags_when_a_historical_offset_was_applied(): void
    {
        $this->assertTrue(
            $this->resolver->usedHistoricalOffset('1980-07-20', '10:30', 'Asia/Kathmandu'),
            'A 1980 Nepali birth should be flagged as using a historical offset.'
        );

        $this->assertFalse(
            $this->resolver->usedHistoricalOffset('2000-07-20', '10:30', 'Asia/Kathmandu'),
            'A 2000 Nepali birth uses the same offset as today.'
        );
    }

    #[Test]
    public function it_handles_the_exact_1986_transition_boundary(): void
    {
        $before = $this->resolver->offsetHours('1985-12-31', '23:00', 'Asia/Kathmandu');
        $after = $this->resolver->offsetHours('1986-01-02', '01:00', 'Asia/Kathmandu');

        $this->assertSame(5.5, $before);
        $this->assertSame(5.75, $after);
    }

    #[Test]
    public function it_applies_indian_standard_time(): void
    {
        $utc = $this->resolver->toUtc('1995-03-10', '14:15', 'Asia/Kolkata');

        $this->assertSame('1995-03-10 08:45:00', $utc->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_applies_daylight_saving_where_it_was_in_force(): void
    {
        // London, mid-summer = BST (+1)
        $summer = $this->resolver->toUtc('1990-07-15', '12:00', 'Europe/London');
        $this->assertSame('1990-07-15 11:00:00', $summer->format('Y-m-d H:i:s'));

        // London, mid-winter = GMT (+0)
        $winter = $this->resolver->toUtc('1990-01-15', '12:00', 'Europe/London');
        $this->assertSame('1990-01-15 12:00:00', $winter->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_handles_a_southern_hemisphere_birth(): void
    {
        // Sydney in January is on AEDT (+11)
        $utc = $this->resolver->toUtc('1992-01-20', '09:00', 'Australia/Sydney');

        $this->assertSame('1992-01-19 22:00:00', $utc->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_accepts_times_with_seconds(): void
    {
        $utc = $this->resolver->toUtc('2000-01-01', '06:15:30', 'Asia/Kathmandu');

        $this->assertSame('2000-01-01 00:30:30', $utc->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_rejects_an_unknown_timezone(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resolver->toUtc('1990-05-15', '10:30', 'Asia/Nowhere');
    }

    #[Test]
    public function it_rejects_a_malformed_time(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resolver->toUtc('1990-05-15', '25 past ten', 'Asia/Kathmandu');
    }

    #[Test]
    public function it_rejects_an_out_of_range_time(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resolver->toUtc('1990-05-15', '25:99', 'Asia/Kathmandu');
    }
}
