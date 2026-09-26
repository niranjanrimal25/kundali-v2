<?php

namespace App\Services\Astrology\Support;

/**
 * Static reference data for Vedic astrology: signs, lords, elements,
 * dignities, aspects and house significations.
 *
 * All sign indexes are 0-based (0 = Aries ... 11 = Pisces).
 * All house numbers are 1-based (1 = Lagna ... 12 = Vyaya).
 */
class Zodiac
{
    public const SIGNS = [
        0 => 'Aries', 1 => 'Taurus', 2 => 'Gemini', 3 => 'Cancer',
        4 => 'Leo', 5 => 'Virgo', 6 => 'Libra', 7 => 'Scorpio',
        8 => 'Sagittarius', 9 => 'Capricorn', 10 => 'Aquarius', 11 => 'Pisces',
    ];

    public const SIGNS_SANSKRIT = [
        0 => 'Mesha', 1 => 'Vrishabha', 2 => 'Mithuna', 3 => 'Karka',
        4 => 'Simha', 5 => 'Kanya', 6 => 'Tula', 7 => 'Vrischika',
        8 => 'Dhanu', 9 => 'Makara', 10 => 'Kumbha', 11 => 'Meena',
    ];

    public const SIGN_LORDS = [
        0 => 'Mars', 1 => 'Venus', 2 => 'Mercury', 3 => 'Moon',
        4 => 'Sun', 5 => 'Mercury', 6 => 'Venus', 7 => 'Mars',
        8 => 'Jupiter', 9 => 'Saturn', 10 => 'Saturn', 11 => 'Jupiter',
    ];

    /** Fire, Earth, Air, Water repeating */
    public const ELEMENTS = [
        0 => 'Fire', 1 => 'Earth', 2 => 'Air', 3 => 'Water',
        4 => 'Fire', 5 => 'Earth', 6 => 'Air', 7 => 'Water',
        8 => 'Fire', 9 => 'Earth', 10 => 'Air', 11 => 'Water',
    ];

    /** Chara (movable), Sthira (fixed), Dvisvabhava (dual) */
    public const MODALITIES = [
        0 => 'Movable', 1 => 'Fixed', 2 => 'Dual', 3 => 'Movable',
        4 => 'Fixed', 5 => 'Dual', 6 => 'Movable', 7 => 'Fixed',
        8 => 'Dual', 9 => 'Movable', 10 => 'Fixed', 11 => 'Dual',
    ];

    public const SIGN_GENDER = [
        0 => 'Male', 1 => 'Female', 2 => 'Male', 3 => 'Female',
        4 => 'Male', 5 => 'Female', 6 => 'Male', 7 => 'Female',
        8 => 'Male', 9 => 'Female', 10 => 'Male', 11 => 'Female',
    ];

    /** Grahas in Vimshottari / classical order. */
    public const PLANETS = [
        'Sun', 'Moon', 'Mars', 'Mercury', 'Jupiter', 'Venus', 'Saturn', 'Rahu', 'Ketu',
    ];

    public const PLANETS_SANSKRIT = [
        'Sun' => 'Surya', 'Moon' => 'Chandra', 'Mars' => 'Mangala',
        'Mercury' => 'Budha', 'Jupiter' => 'Guru', 'Venus' => 'Shukra',
        'Saturn' => 'Shani', 'Rahu' => 'Rahu', 'Ketu' => 'Ketu',
    ];

    /** Natural benefic / malefic (Mercury and Moon are conditional). */
    public const NATURE = [
        'Sun' => 'malefic', 'Moon' => 'benefic', 'Mars' => 'malefic',
        'Mercury' => 'benefic', 'Jupiter' => 'benefic', 'Venus' => 'benefic',
        'Saturn' => 'malefic', 'Rahu' => 'malefic', 'Ketu' => 'malefic',
    ];

    /** Signs each planet owns. */
    public const OWN_SIGNS = [
        'Sun' => [4], 'Moon' => [3], 'Mars' => [0, 7], 'Mercury' => [2, 5],
        'Jupiter' => [8, 11], 'Venus' => [1, 6], 'Saturn' => [9, 10],
        'Rahu' => [], 'Ketu' => [],
    ];

    /** [sign, exact degree of deep exaltation] */
    public const EXALTATION = [
        'Sun' => [0, 10.0], 'Moon' => [1, 3.0], 'Mars' => [9, 28.0],
        'Mercury' => [5, 15.0], 'Jupiter' => [3, 5.0], 'Venus' => [11, 27.0],
        'Saturn' => [6, 20.0], 'Rahu' => [1, 20.0], 'Ketu' => [7, 20.0],
    ];

    /** Debilitation is exactly opposite the exaltation point. */
    public const DEBILITATION = [
        'Sun' => [6, 10.0], 'Moon' => [7, 3.0], 'Mars' => [3, 28.0],
        'Mercury' => [11, 15.0], 'Jupiter' => [9, 5.0], 'Venus' => [5, 27.0],
        'Saturn' => [0, 20.0], 'Rahu' => [7, 20.0], 'Ketu' => [1, 20.0],
    ];

    /** Moolatrikona: [sign, startDegree, endDegree] */
    public const MOOLATRIKONA = [
        'Sun' => [4, 0.0, 20.0],
        'Moon' => [1, 3.0, 30.0],
        'Mars' => [0, 0.0, 12.0],
        'Mercury' => [5, 16.0, 20.0],
        'Jupiter' => [8, 0.0, 10.0],
        'Venus' => [6, 0.0, 15.0],
        'Saturn' => [10, 0.0, 20.0],
    ];

    /** Naisargika (natural) planetary friendships. */
    public const FRIENDS = [
        'Sun' => ['Moon', 'Mars', 'Jupiter'],
        'Moon' => ['Sun', 'Mercury'],
        'Mars' => ['Sun', 'Moon', 'Jupiter'],
        'Mercury' => ['Sun', 'Venus'],
        'Jupiter' => ['Sun', 'Moon', 'Mars'],
        'Venus' => ['Mercury', 'Saturn'],
        'Saturn' => ['Mercury', 'Venus'],
        'Rahu' => ['Venus', 'Saturn', 'Mercury'],
        'Ketu' => ['Mars', 'Venus', 'Saturn'],
    ];

    public const ENEMIES = [
        'Sun' => ['Venus', 'Saturn'],
        'Moon' => [],
        'Mars' => ['Mercury'],
        'Mercury' => ['Moon'],
        'Jupiter' => ['Mercury', 'Venus'],
        'Venus' => ['Sun', 'Moon'],
        'Saturn' => ['Sun', 'Moon', 'Mars'],
        'Rahu' => ['Sun', 'Moon', 'Mars'],
        'Ketu' => ['Sun', 'Moon'],
    ];

    public const NEUTRALS = [
        'Sun' => ['Mercury'],
        'Moon' => ['Mars', 'Jupiter', 'Venus', 'Saturn'],
        'Mars' => ['Venus', 'Saturn'],
        'Mercury' => ['Mars', 'Jupiter', 'Saturn'],
        'Jupiter' => ['Saturn'],
        'Venus' => ['Mars', 'Jupiter'],
        'Saturn' => ['Jupiter'],
        'Rahu' => ['Jupiter'],
        'Ketu' => ['Mercury', 'Jupiter'],
    ];

    /**
     * Special aspects beyond the universal 7th.
     * Values are house-distances counted from the planet's own house.
     */
    public const SPECIAL_ASPECTS = [
        'Mars' => [4, 7, 8],
        'Jupiter' => [5, 7, 9],
        'Saturn' => [3, 7, 10],
        'Rahu' => [5, 7, 9],
        'Ketu' => [5, 7, 9],
    ];

    public const DEFAULT_ASPECT = [7];

    /**
     * Digbala — directional strength. A planet gains full directional
     * strength in the house listed, and is weakest in the opposite house.
     */
    public const DIGBALA_HOUSE = [
        'Jupiter' => 1, 'Mercury' => 1,   // East  — Lagna
        'Sun' => 10, 'Mars' => 10,        // South — Madhya
        'Saturn' => 7,                    // West  — Descendant
        'Moon' => 4, 'Venus' => 4,        // North — Nadir
    ];

    public const DIRECTIONS = [
        1 => 'East', 4 => 'North', 7 => 'West', 10 => 'South',
    ];

    /** House names and their core significations. */
    public const HOUSES = [
        1 => ['name' => 'Tanu Bhava', 'english' => 'Self & Body', 'significations' => 'physical body, appearance, temperament, vitality, overall direction of life'],
        2 => ['name' => 'Dhana Bhava', 'english' => 'Wealth & Family', 'significations' => 'accumulated wealth, speech, immediate family, food, values, right eye'],
        3 => ['name' => 'Sahaja Bhava', 'english' => 'Courage & Siblings', 'significations' => 'courage, younger siblings, short journeys, communication, hands, effort'],
        4 => ['name' => 'Sukha Bhava', 'english' => 'Home & Mother', 'significations' => 'mother, home, land, vehicles, inner happiness, education, chest'],
        5 => ['name' => 'Putra Bhava', 'english' => 'Intellect & Children', 'significations' => 'children, intelligence, creativity, romance, past-life merit, speculation'],
        6 => ['name' => 'Ripu Bhava', 'english' => 'Enemies & Health', 'significations' => 'disease, debts, enemies, litigation, service, daily work, maternal uncle'],
        7 => ['name' => 'Kalatra Bhava', 'english' => 'Marriage & Partnership', 'significations' => 'spouse, marriage, business partnerships, contracts, public dealings'],
        8 => ['name' => 'Ayur Bhava', 'english' => 'Longevity & Transformation', 'significations' => 'longevity, sudden events, inheritance, occult, chronic illness, research'],
        9 => ['name' => 'Bhagya Bhava', 'english' => 'Fortune & Dharma', 'significations' => 'fortune, father, guru, higher learning, long journeys, religion, ethics'],
        10 => ['name' => 'Karma Bhava', 'english' => 'Career & Status', 'significations' => 'profession, status, authority, reputation, karma in the world'],
        11 => ['name' => 'Labha Bhava', 'english' => 'Gains & Aspirations', 'significations' => 'income, gains, elder siblings, friends, fulfilment of desires, networks'],
        12 => ['name' => 'Vyaya Bhava', 'english' => 'Loss & Liberation', 'significations' => 'expenditure, losses, foreign lands, isolation, sleep, moksha, left eye'],
    ];

    public const KENDRA = [1, 4, 7, 10];

    public const TRIKONA = [1, 5, 9];

    public const DUSTHANA = [6, 8, 12];

    public const UPACHAYA = [3, 6, 10, 11];

    public const MARAKA = [2, 7];

    public static function sign(float $longitude): int
    {
        return (int) floor(self::normalize($longitude) / 30.0) % 12;
    }

    public static function degreeInSign(float $longitude): float
    {
        return fmod(self::normalize($longitude), 30.0);
    }

    public static function normalize(float $degrees): float
    {
        $d = fmod($degrees, 360.0);

        return $d < 0 ? $d + 360.0 : $d;
    }

    public static function signName(int $sign): string
    {
        return self::SIGNS[$sign % 12];
    }

    public static function lordOf(int $sign): string
    {
        return self::SIGN_LORDS[$sign % 12];
    }

    /** Which house does $sign fall in, given the Lagna sign? (Whole Sign) */
    public static function houseOfSign(int $sign, int $lagnaSign): int
    {
        return (($sign - $lagnaSign + 12) % 12) + 1;
    }

    /** Which sign occupies $house, given the Lagna sign? (Whole Sign) */
    public static function signOfHouse(int $house, int $lagnaSign): int
    {
        return ($lagnaSign + $house - 1) % 12;
    }

    /** Format a degree value as 12°34'56". */
    public static function formatDegree(float $degrees): string
    {
        $d = (int) floor($degrees);
        $mFloat = ($degrees - $d) * 60;
        $m = (int) floor($mFloat);
        $s = (int) round(($mFloat - $m) * 60);

        if ($s === 60) {
            $s = 0;
            $m++;
        }
        if ($m === 60) {
            $m = 0;
            $d++;
        }

        return sprintf('%d°%02d\'%02d"', $d, $m, $s);
    }
}
