<?php

namespace App\Services\Astrology;

use App\Models\Kundali;
use App\Services\Astrology\Ephemeris\EphemerisInterface;
use App\Services\Astrology\Support\Nakshatras;
use App\Services\Astrology\Support\Zodiac;

/**
 * Layer 2 — the Vedic mathematics layer.
 *
 * Takes raw sidereal longitudes from the ephemeris and derives every
 * structural fact a Parashari astrologer reasons from: signs, houses,
 * nakshatras, dignity, directional strength, aspects, house lordships,
 * divisional charts and the Vimshottari dasha tree.
 *
 * The output is a plain array ("ChartFacts"). The interpretation layer
 * reads only this structure and never touches astronomy, which keeps the
 * two concerns independently testable.
 */
class ChartCalculator
{
    public const ENGINE_VERSION = '1.0';

    public function __construct(
        private readonly EphemerisInterface $ephemeris,
        private readonly TimeResolver $time,
        private readonly VimshottariDasha $dasha,
        private readonly YogaDetector $yogas,
        private readonly DoshaDetector $doshas,
    ) {}

    public function forKundali(Kundali $kundali): array
    {
        return $this->calculate(
            $kundali->birth_date->format('Y-m-d'),
            (string) $kundali->birth_time,
            $kundali->timezone,
            (float) $kundali->latitude,
            (float) $kundali->longitude,
        );
    }

    public function calculate(
        string $date,
        string $time,
        string $timezone,
        float $latitude,
        float $longitude,
    ): array {
        $utc = $this->time->toUtc($date, $time, $timezone);

        $raw = $this->ephemeris->calculate($utc, $latitude, $longitude);

        $ascendant = Zodiac::normalize($raw['ascendant']);
        $lagnaSign = Zodiac::sign($ascendant);

        $planets = $this->buildPlanets($raw['planets'], $lagnaSign, $raw['planets']['Sun']['longitude']);
        $houses = $this->buildHouses($lagnaSign, $planets);

        $planets = $this->applyAspects($planets, $lagnaSign);

        $moonLongitude = $planets['Moon']['longitude'];

        return [
            'meta' => [
                'engine_version' => self::ENGINE_VERSION,
                'ayanamsa_name' => ucfirst((string) config('jyotish.ayanamsa')),
                'ayanamsa_value' => round($raw['ayanamsa'], 6),
                'ayanamsa_formatted' => Zodiac::formatDegree($raw['ayanamsa']),
                'house_system' => config('jyotish.house_system') === 'W' ? 'Whole Sign' : config('jyotish.house_system'),
                'julian_day' => $raw['julian_day'],
                'utc' => $utc->format('Y-m-d H:i:s'),
                'local_time' => "{$date} {$time}",
                'timezone' => $timezone,
                'utc_offset' => $this->time->offsetLabel($date, $time, $timezone),
                'utc_offset_hours' => $this->time->offsetHours($date, $time, $timezone),
                'historical_offset_applied' => $this->time->usedHistoricalOffset($date, $time, $timezone),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],

            'lagna' => [
                'longitude' => round($ascendant, 6),
                'sign' => $lagnaSign,
                'sign_name' => Zodiac::SIGNS[$lagnaSign],
                'sign_sanskrit' => Zodiac::SIGNS_SANSKRIT[$lagnaSign],
                'degree_in_sign' => round(Zodiac::degreeInSign($ascendant), 6),
                'degree_formatted' => Zodiac::formatDegree(Zodiac::degreeInSign($ascendant)),
                'lord' => Zodiac::lordOf($lagnaSign),
                'element' => Zodiac::ELEMENTS[$lagnaSign],
                'modality' => Zodiac::MODALITIES[$lagnaSign],
                'nakshatra' => Nakshatras::describe($ascendant),
            ],

            'planets' => $planets,
            'houses' => $houses,

            'moon' => [
                'sign' => $planets['Moon']['sign'],
                'sign_name' => $planets['Moon']['sign_name'],
                'rashi' => Zodiac::SIGNS_SANSKRIT[$planets['Moon']['sign']],
                'nakshatra' => Nakshatras::describe($moonLongitude),
            ],

            'divisional' => [
                'D9' => $this->navamsaChart($planets),
            ],

            'dasha' => $this->dasha->build($moonLongitude, $utc),

            'yogas' => $this->yogas->detect($planets, $lagnaSign),
            'doshas' => $this->doshas->detect($planets, $lagnaSign, $houses),
            'transits' => $this->transits($planets),
        ];
    }

    /**
     * Transit-dependent findings. Sade Sati is the only element of a
     * standard reading that depends on where Shani is TODAY rather than
     * at birth, so it is computed against the current ephemeris.
     *
     * A transit failure must never break a birth chart, so this degrades
     * to an empty result rather than throwing.
     */
    private function transits(array $planets): array
    {
        try {
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

            // Longitude/latitude are irrelevant for a planetary longitude;
            // Greenwich is used simply because a location is required.
            $raw = $this->ephemeris->calculate($now, 0.0, 0.0);

            $saturn = $raw['planets']['Saturn']['longitude'] ?? null;

            if ($saturn === null) {
                return [];
            }

            $sadeSati = $this->doshas->sadeSati($planets, $saturn);

            return [
                'computed_at' => $now->format('Y-m-d'),
                'saturn_longitude' => round($saturn, 6),
                'saturn_sign' => Zodiac::SIGNS[(int) floor($saturn / 30) % 12],
                'sade_sati' => $sadeSati,
            ];
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Build the per-planet fact block.
     */
    private function buildPlanets(array $rawPlanets, int $lagnaSign, float $sunLongitude): array
    {
        $planets = [];

        foreach (Zodiac::PLANETS as $name) {
            $data = $rawPlanets[$name];
            $longitude = Zodiac::normalize($data['longitude']);
            $sign = Zodiac::sign($longitude);
            $degreeInSign = Zodiac::degreeInSign($longitude);
            $house = Zodiac::houseOfSign($sign, $lagnaSign);

            $dignity = $this->dignity($name, $sign, $degreeInSign);

            $planets[$name] = [
                'name' => $name,
                'sanskrit' => Zodiac::PLANETS_SANSKRIT[$name],
                'longitude' => round($longitude, 6),
                'sign' => $sign,
                'sign_name' => Zodiac::SIGNS[$sign],
                'sign_sanskrit' => Zodiac::SIGNS_SANSKRIT[$sign],
                'degree_in_sign' => round($degreeInSign, 6),
                'degree_formatted' => Zodiac::formatDegree($degreeInSign),
                'house' => $house,
                'house_name' => Zodiac::HOUSES[$house]['name'],
                'speed' => round($data['speed'], 6),
                'retrograde' => $data['retrograde'],
                'nakshatra' => Nakshatras::describe($longitude),
                'dignity' => $dignity,
                'dignity_score' => $this->dignityScore($dignity),
                'nature' => Zodiac::NATURE[$name],
                'element' => Zodiac::ELEMENTS[$sign],
                'sign_lord' => Zodiac::lordOf($sign),
                'combust' => $this->isCombust($name, $longitude, $sunLongitude, $data['retrograde']),
                'digbala' => $this->digbala($name, $house),
                'owns_houses' => $this->housesOwnedBy($name, $lagnaSign),
                'navamsa_sign' => $this->navamsaSign($longitude),
                'vargottama' => $sign === $this->navamsaSign($longitude),
            ];
        }

        // Conjunctions require all planets placed first.
        foreach ($planets as $name => $planet) {
            $planets[$name]['conjunct'] = $this->conjunctions($name, $planets);
        }

        return $planets;
    }

    /**
     * Assemble the 12 bhavas with their sign, lord, occupants and
     * the lord's own placement — the backbone of Parashari prediction.
     */
    private function buildHouses(int $lagnaSign, array $planets): array
    {
        $houses = [];

        for ($house = 1; $house <= 12; $house++) {
            $sign = Zodiac::signOfHouse($house, $lagnaSign);
            $lord = Zodiac::lordOf($sign);

            $occupants = [];
            foreach ($planets as $name => $planet) {
                if ($planet['house'] === $house) {
                    $occupants[] = $name;
                }
            }

            $houses[$house] = [
                'number' => $house,
                'name' => Zodiac::HOUSES[$house]['name'],
                'english' => Zodiac::HOUSES[$house]['english'],
                'significations' => Zodiac::HOUSES[$house]['significations'],
                'sign' => $sign,
                'sign_name' => Zodiac::SIGNS[$sign],
                'sign_sanskrit' => Zodiac::SIGNS_SANSKRIT[$sign],
                'element' => Zodiac::ELEMENTS[$sign],
                'modality' => Zodiac::MODALITIES[$sign],
                'lord' => $lord,
                'lord_house' => $planets[$lord]['house'] ?? null,
                'lord_sign' => $planets[$lord]['sign'] ?? null,
                'lord_dignity' => $planets[$lord]['dignity'] ?? null,
                'lord_retrograde' => $planets[$lord]['retrograde'] ?? false,
                'occupants' => $occupants,
                'is_kendra' => in_array($house, Zodiac::KENDRA, true),
                'is_trikona' => in_array($house, Zodiac::TRIKONA, true),
                'is_dusthana' => in_array($house, Zodiac::DUSTHANA, true),
                'is_upachaya' => in_array($house, Zodiac::UPACHAYA, true),
                'aspected_by' => [],
            ];
        }

        return $houses;
    }

    /**
     * Graha Drishti. Every planet aspects the 7th from itself; Mars,
     * Jupiter, Saturn and the nodes have additional special aspects.
     */
    private function applyAspects(array $planets, int $lagnaSign): array
    {
        foreach ($planets as $name => $planet) {
            $distances = Zodiac::SPECIAL_ASPECTS[$name] ?? Zodiac::DEFAULT_ASPECT;

            $aspected = [];
            foreach ($distances as $distance) {
                $target = (($planet['house'] - 1 + $distance - 1) % 12) + 1;
                $aspected[] = $target;
            }

            $planets[$name]['aspects_houses'] = $aspected;

            // Which planets fall in those aspected houses
            $aspectedPlanets = [];
            foreach ($planets as $other => $otherPlanet) {
                if ($other !== $name && in_array($otherPlanet['house'], $aspected, true)) {
                    $aspectedPlanets[] = $other;
                }
            }
            $planets[$name]['aspects_planets'] = $aspectedPlanets;
        }

        return $planets;
    }

    /**
     * Classical dignity ladder. Moolatrikona is checked before own-sign
     * because it is the stronger placement within the same sign.
     */
    private function dignity(string $planet, int $sign, float $degreeInSign): string
    {
        if (isset(Zodiac::EXALTATION[$planet]) && Zodiac::EXALTATION[$planet][0] === $sign) {
            return 'exalted';
        }

        if (isset(Zodiac::DEBILITATION[$planet]) && Zodiac::DEBILITATION[$planet][0] === $sign) {
            return 'debilitated';
        }

        if (isset(Zodiac::MOOLATRIKONA[$planet])) {
            [$mtSign, $start, $end] = Zodiac::MOOLATRIKONA[$planet];
            if ($mtSign === $sign && $degreeInSign >= $start && $degreeInSign <= $end) {
                return 'moolatrikona';
            }
        }

        if (in_array($sign, Zodiac::OWN_SIGNS[$planet] ?? [], true)) {
            return 'own';
        }

        // Rahu and Ketu own no sign; judge them by the dispositor.
        $dispositor = Zodiac::lordOf($sign);

        if (in_array($dispositor, Zodiac::FRIENDS[$planet] ?? [], true)) {
            return 'friendly';
        }

        if (in_array($dispositor, Zodiac::ENEMIES[$planet] ?? [], true)) {
            return 'enemy';
        }

        return 'neutral';
    }

    /** Numeric strength so the composer can modulate its language. */
    private function dignityScore(string $dignity): int
    {
        return match ($dignity) {
            'exalted' => 5,
            'moolatrikona' => 4,
            'own' => 4,
            'friendly' => 3,
            'neutral' => 2,
            'enemy' => 1,
            'debilitated' => 0,
            default => 2,
        };
    }

    /**
     * Combustion — proximity to the Sun burns a planet's significations.
     */
    private function isCombust(string $planet, float $longitude, float $sunLongitude, bool $retrograde): bool
    {
        if (in_array($planet, ['Sun', 'Rahu', 'Ketu'], true)) {
            return false;
        }

        $orbs = config('jyotish.combustion_orbs');
        $key = $retrograde && isset($orbs[$planet.'_retro']) ? $planet.'_retro' : $planet;
        $orb = $orbs[$key] ?? null;

        if ($orb === null) {
            return false;
        }

        $separation = abs(Zodiac::normalize($longitude) - Zodiac::normalize($sunLongitude));
        if ($separation > 180) {
            $separation = 360 - $separation;
        }

        return $separation <= $orb;
    }

    /**
     * Digbala — directional strength. Full strength in the designated
     * house, zero in the opposite, proportional in between.
     */
    private function digbala(string $planet, int $house): array
    {
        $strongHouse = Zodiac::DIGBALA_HOUSE[$planet] ?? null;

        if ($strongHouse === null) {
            return ['applicable' => false, 'strength' => null, 'direction' => null, 'label' => 'not applicable'];
        }

        $weakHouse = (($strongHouse + 6 - 1) % 12) + 1;

        // Angular distance in houses from the point of full strength.
        $distance = min(
            ($house - $strongHouse + 12) % 12,
            ($strongHouse - $house + 12) % 12
        );

        // 0 houses away = 1.0, 6 houses away = 0.0
        $strength = 1.0 - ($distance / 6);

        return [
            'applicable' => true,
            'strength' => round($strength, 3),
            'strong_house' => $strongHouse,
            'weak_house' => $weakHouse,
            'direction' => Zodiac::DIRECTIONS[$strongHouse] ?? null,
            'label' => match (true) {
                $strength >= 0.84 => 'full directional strength',
                $strength >= 0.6 => 'strong directionally',
                $strength >= 0.4 => 'moderate directionally',
                $strength > 0.16 => 'weak directionally',
                default => 'directionally powerless',
            },
        ];
    }

    /** Houses this planet rules, relative to the Lagna. */
    private function housesOwnedBy(string $planet, int $lagnaSign): array
    {
        $houses = [];

        foreach (Zodiac::OWN_SIGNS[$planet] ?? [] as $sign) {
            $houses[] = Zodiac::houseOfSign($sign, $lagnaSign);
        }

        sort($houses);

        return $houses;
    }

    /** Planets sharing the same sign. */
    private function conjunctions(string $planet, array $planets): array
    {
        $sign = $planets[$planet]['sign'];

        $conjunct = [];
        foreach ($planets as $other => $data) {
            if ($other !== $planet && $data['sign'] === $sign) {
                $conjunct[] = $other;
            }
        }

        return $conjunct;
    }

    /**
     * Navamsa (D9) sign — the ninth harmonic, read for marriage and for
     * the underlying strength of a planet.
     *
     * Each sign divides into nine 3°20' parts. The counting start differs
     * by element: Fire signs start from Aries, Earth from Capricorn,
     * Air from Libra, Water from Cancer.
     */
    private function navamsaSign(float $longitude): int
    {
        $longitude = Zodiac::normalize($longitude);
        $sign = Zodiac::sign($longitude);
        $degreeInSign = Zodiac::degreeInSign($longitude);

        $navamsaIndex = (int) floor($degreeInSign / 3.3333333333333335);

        $start = match ($sign % 4) {
            0 => 0,   // Movable: Aries, Cancer, Libra, Capricorn → start at own sign
            default => 0,
        };

        // Standard rule: movable signs start from themselves, fixed from
        // the 9th from themselves, dual from the 5th from themselves.
        $modality = Zodiac::MODALITIES[$sign];
        $start = match ($modality) {
            'Movable' => $sign,
            'Fixed' => ($sign + 8) % 12,
            'Dual' => ($sign + 4) % 12,
        };

        return ($start + $navamsaIndex) % 12;
    }

    /** Full D9 chart with its own ascendant and house placements. */
    private function navamsaChart(array $planets): array
    {
        $chart = [];

        foreach ($planets as $name => $planet) {
            $navSign = $planet['navamsa_sign'];
            $chart[$name] = [
                'sign' => $navSign,
                'sign_name' => Zodiac::SIGNS[$navSign],
                'vargottama' => $planet['vargottama'],
            ];
        }

        return $chart;
    }
}
