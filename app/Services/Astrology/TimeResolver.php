<?php

namespace App\Services\Astrology;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Converts a local civil birth time into UTC using the HISTORICALLY correct
 * offset for that date and place.
 *
 * This is the single largest source of wrong Kundalis. A four-minute error
 * shifts the Lagna by roughly one degree; a one-hour error can move it into
 * an adjacent sign and invalidate the entire reading.
 *
 * PHP's timezone database (IANA/Olson) already encodes every historical
 * transition, so we deliberately resolve offsets through DateTimeZone rather
 * than hardcoding them. Notable cases this handles automatically:
 *
 *   Nepal   — UTC+5:30 until 1985-12-31, UTC+5:45 from 1986-01-01
 *   India   — UTC+5:30, with wartime DST in 1941-1945
 *   Europe  — annual DST transitions, differing by country and era
 */
class TimeResolver
{
    /**
     * @param  string  $date  Y-m-d in the local civil calendar
     * @param  string  $time  H:i or H:i:s local civil time
     * @param  string  $timezone  IANA identifier, e.g. "Asia/Kathmandu"
     */
    public function toUtc(string $date, string $time, string $timezone): DateTimeImmutable
    {
        $tz = $this->resolveZone($timezone);

        $time = $this->normalizeTime($time);

        $local = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            "{$date} {$time}",
            $tz
        );

        if ($local === false) {
            throw new InvalidArgumentException("Could not parse birth moment '{$date} {$time}'.");
        }

        // createFromFormat tolerates overflow (e.g. 25:00 → next day), so
        // verify the round-trip to catch genuinely invalid input.
        if ($local->format('Y-m-d H:i:s') !== "{$date} {$time}") {
            throw new InvalidArgumentException(
                "Invalid birth moment '{$date} {$time}' for timezone {$timezone}. ".
                'This can also occur if the time falls inside a daylight-saving gap.'
            );
        }

        return $local->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * The UTC offset actually applied, in hours (e.g. 5.75 for Nepal today).
     * Surfaced in the UI so the user can sanity-check it against the
     * birth certificate before trusting the chart.
     */
    public function offsetHours(string $date, string $time, string $timezone): float
    {
        $tz = $this->resolveZone($timezone);

        $local = new DateTimeImmutable("{$date} {$this->normalizeTime($time)}", $tz);

        return $tz->getOffset($local) / 3600;
    }

    /** Human-readable offset such as "+05:45". */
    public function offsetLabel(string $date, string $time, string $timezone): string
    {
        $hours = $this->offsetHours($date, $time, $timezone);
        $sign = $hours < 0 ? '-' : '+';
        $abs = abs($hours);

        return sprintf('%s%02d:%02d', $sign, (int) $abs, (int) round(($abs - (int) $abs) * 60));
    }

    /**
     * True when the offset in force at birth differs from the offset in
     * force today. Used to show a reassurance note in the UI for older
     * Nepali births, where +5:30 rather than +5:45 is the correct value.
     */
    public function usedHistoricalOffset(string $date, string $time, string $timezone): bool
    {
        $tz = $this->resolveZone($timezone);

        $birth = new DateTimeImmutable("{$date} {$this->normalizeTime($time)}", $tz);
        $now = new DateTimeImmutable('now', $tz);

        return $tz->getOffset($birth) !== $tz->getOffset($now);
    }

    private function resolveZone(string $timezone): DateTimeZone
    {
        try {
            return new DateTimeZone($timezone);
        } catch (\Exception) {
            throw new InvalidArgumentException("Unknown timezone identifier '{$timezone}'.");
        }
    }

    private function normalizeTime(string $time): string
    {
        $time = trim($time);

        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            return $time.':00';
        }

        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        throw new InvalidArgumentException("Birth time '{$time}' must be in HH:MM or HH:MM:SS format.");
    }
}
