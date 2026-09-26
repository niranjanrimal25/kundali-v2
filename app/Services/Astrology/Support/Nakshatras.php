<?php

namespace App\Services\Astrology\Support;

/**
 * The 27 Nakshatras (lunar mansions), each spanning 13°20' of the
 * sidereal zodiac, subdivided into 4 padas of 3°20' each.
 *
 * The Vimshottari Dasha lords cycle Ketu → Venus → Sun → Moon → Mars →
 * Rahu → Jupiter → Saturn → Mercury, repeating three times across the 27.
 */
class Nakshatras
{
    public const SPAN = 13.333333333333334;   // 13°20'

    public const PADA_SPAN = 3.3333333333333335; // 3°20'

    public const NAMES = [
        0 => 'Ashwini', 1 => 'Bharani', 2 => 'Krittika', 3 => 'Rohini',
        4 => 'Mrigashira', 5 => 'Ardra', 6 => 'Punarvasu', 7 => 'Pushya',
        8 => 'Ashlesha', 9 => 'Magha', 10 => 'Purva Phalguni', 11 => 'Uttara Phalguni',
        12 => 'Hasta', 13 => 'Chitra', 14 => 'Swati', 15 => 'Vishakha',
        16 => 'Anuradha', 17 => 'Jyeshtha', 18 => 'Mula', 19 => 'Purva Ashadha',
        20 => 'Uttara Ashadha', 21 => 'Shravana', 22 => 'Dhanishta', 23 => 'Shatabhisha',
        24 => 'Purva Bhadrapada', 25 => 'Uttara Bhadrapada', 26 => 'Revati',
    ];

    /** Vimshottari dasha lord of each nakshatra. */
    public const LORDS = [
        0 => 'Ketu', 1 => 'Venus', 2 => 'Sun', 3 => 'Moon', 4 => 'Mars',
        5 => 'Rahu', 6 => 'Jupiter', 7 => 'Saturn', 8 => 'Mercury',
        9 => 'Ketu', 10 => 'Venus', 11 => 'Sun', 12 => 'Moon', 13 => 'Mars',
        14 => 'Rahu', 15 => 'Jupiter', 16 => 'Saturn', 17 => 'Mercury',
        18 => 'Ketu', 19 => 'Venus', 20 => 'Sun', 21 => 'Moon', 22 => 'Mars',
        23 => 'Rahu', 24 => 'Jupiter', 25 => 'Saturn', 26 => 'Mercury',
    ];

    /** Ruling deity — used in interpretation for temperament. */
    public const DEITIES = [
        0 => 'Ashwini Kumaras', 1 => 'Yama', 2 => 'Agni', 3 => 'Brahma',
        4 => 'Soma', 5 => 'Rudra', 6 => 'Aditi', 7 => 'Brihaspati',
        8 => 'Nagas', 9 => 'Pitris', 10 => 'Bhaga', 11 => 'Aryaman',
        12 => 'Savitr', 13 => 'Tvashtar', 14 => 'Vayu', 15 => 'Indra-Agni',
        16 => 'Mitra', 17 => 'Indra', 18 => 'Nirriti', 19 => 'Apas',
        20 => 'Vishvadevas', 21 => 'Vishnu', 22 => 'Vasus', 23 => 'Varuna',
        24 => 'Aja Ekapada', 25 => 'Ahir Budhnya', 26 => 'Pushan',
    ];

    /** Gana — Deva (divine), Manushya (human), Rakshasa (demonic). */
    public const GANA = [
        0 => 'Deva', 1 => 'Manushya', 2 => 'Rakshasa', 3 => 'Manushya',
        4 => 'Deva', 5 => 'Manushya', 6 => 'Deva', 7 => 'Deva',
        8 => 'Rakshasa', 9 => 'Rakshasa', 10 => 'Manushya', 11 => 'Manushya',
        12 => 'Deva', 13 => 'Rakshasa', 14 => 'Deva', 15 => 'Rakshasa',
        16 => 'Deva', 17 => 'Rakshasa', 18 => 'Rakshasa', 19 => 'Manushya',
        20 => 'Manushya', 21 => 'Deva', 22 => 'Rakshasa', 23 => 'Rakshasa',
        24 => 'Manushya', 25 => 'Manushya', 26 => 'Deva',
    ];

    /** Nadi — Adi (Vata), Madhya (Pitta), Antya (Kapha). Used in matching. */
    public const NADI = [
        0 => 'Adi', 1 => 'Madhya', 2 => 'Antya', 3 => 'Adi', 4 => 'Madhya',
        5 => 'Antya', 6 => 'Adi', 7 => 'Madhya', 8 => 'Antya',
        9 => 'Adi', 10 => 'Madhya', 11 => 'Antya', 12 => 'Adi', 13 => 'Madhya',
        14 => 'Antya', 15 => 'Adi', 16 => 'Madhya', 17 => 'Antya',
        18 => 'Adi', 19 => 'Madhya', 20 => 'Antya', 21 => 'Adi', 22 => 'Madhya',
        23 => 'Antya', 24 => 'Adi', 25 => 'Madhya', 26 => 'Antya',
    ];

    /** Index of the nakshatra (0-26) containing this sidereal longitude. */
    public static function index(float $longitude): int
    {
        return (int) floor(Zodiac::normalize($longitude) / self::SPAN) % 27;
    }

    /** Pada 1-4 within the nakshatra. */
    public static function pada(float $longitude): int
    {
        $within = fmod(Zodiac::normalize($longitude), self::SPAN);

        return (int) floor($within / self::PADA_SPAN) + 1;
    }

    /** How far through the nakshatra, as a fraction 0.0-1.0. */
    public static function fractionTraversed(float $longitude): float
    {
        return fmod(Zodiac::normalize($longitude), self::SPAN) / self::SPAN;
    }

    public static function describe(float $longitude): array
    {
        $index = self::index($longitude);

        return [
            'index' => $index,
            'number' => $index + 1,
            'name' => self::NAMES[$index],
            'lord' => self::LORDS[$index],
            'deity' => self::DEITIES[$index],
            'gana' => self::GANA[$index],
            'nadi' => self::NADI[$index],
            'pada' => self::pada($longitude),
        ];
    }
}
