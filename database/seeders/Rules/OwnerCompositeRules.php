<?php

namespace Database\Seeders\Rules;

/**
 * Compound rules supplied by the project owner.
 *
 * These are the rules that need ConditionMatcher: each one combines
 * lordship, placement, conjunction and sometimes sign or ascendant.
 * None of them could be expressed by the old single-key lookup.
 *
 * Health wording follows the same policy as TrikAfflictionRules: these
 * are read as vulnerabilities to watch, never as diagnoses, and the
 * section carries a disclaimer. Entries touching self-harm are worded
 * to direct the reader toward support.
 *
 * Sign indices: 0 Aries, 1 Taurus, 2 Gemini, 3 Cancer, 4 Leo, 5 Virgo,
 * 6 Libra, 7 Scorpio, 8 Sagittarius, 9 Capricorn, 10 Aquarius, 11 Pisces.
 */
class OwnerCompositeRules
{
    private const PROVENANCE = 'classical';

    private const SOURCE = 'Supplied by project owner';

    public static function all(): array
    {
        $rows = [];

        foreach (self::overrides() as $override) {
            $rows[] = $override;
        }

        foreach (self::rules() as $i => [$key, $polarity, $text, $conditions]) {
            $rows[] = [
                'condition_type' => 'composite',
                'condition_key' => $key,
                'section' => 'afflictions',
                'category' => self::CATEGORY[$key] ?? 'health',
                'polarity' => $polarity,
                'weight' => 96,
                'text' => $text,
                'provenance' => self::PROVENANCE,
                'source' => self::SOURCE,
                'conditions' => json_encode($conditions),
            ];
        }

        return $rows;
    }

    /**
     * Rules that are not composite but still owner-supplied, so they
     * must outrank this project's own wording of the same point.
     */
    public static function overrides(): array
    {
        return [
            [
                'condition_type' => 'digbala_planet',
                'condition_key' => 'Sun:powerless',
                'section' => 'modifier',
                'category' => 'health',
                'polarity' => -1,
                // Weight above DigbalaRules (80) so the owner's wording wins.
                'weight' => 99,
                'text' => 'is directionally weak (no Digbala) in the fourth. The source is careful here: weak does not mean entirely inauspicious, and the bhava still gives its results, only without the force it would carry in the tenth',
                'provenance' => self::PROVENANCE,
                'source' => self::SOURCE,
                'conditions' => null,
            ],
            [
                'condition_type' => 'digbala_planet',
                'condition_key' => 'Sun:full',
                'section' => 'modifier',
                'category' => 'career',
                'polarity' => 2,
                'weight' => 99,
                'text' => 'holds full directional strength (Digbala) in the tenth, which is the most powerful position Surya can occupy',
                'provenance' => self::PROVENANCE,
                'source' => self::SOURCE,
                'conditions' => null,
            ],
        ];
    }

    /**
     * Life area each compound rule speaks to, for the summary grouping.
     * Anything unlisted falls back to health, which is where the bulk of
     * the supplied corpus sits.
     */
    private const CATEGORY = [
        'sun_lord_afflicted' => 'health',
        'sun_lagnesh_nodes_head' => 'health',
        'sun_lagnesh_6_moon_venus' => 'health',
        'sun_lagnesh_8_moon_venus' => 'health',
        'third_lord_arm_fracture' => 'health',
        'sun_rahu_stomach' => 'mind',
        'sun_ketu_spiritual' => 'mind',
        'sun_rahu_mercury_nerves' => 'mind',
        'sun_rahu_mercury_low_degree' => 'mind',
        'sun_saturn_trik' => 'health',
        'sun_mars_fire_injury' => 'health',
        'sun_12_mars_fire_injury' => 'health',
        'sun_venus_reproductive' => 'health',
        'mars_venus_reproductive' => 'health',
        'sun_venus_reproductive_severe' => 'health',
        'sun_mars_rahu_fire' => 'mind',
        'moon_1st_fickle' => 'mind',
        'moon_2_6_10_unstable' => 'mind',
        'fourth_lord_chest' => 'health',
        'sixth_lord_blood' => 'health',
        'moon_12_fire_water' => 'mind',
        'moon_aries_lagna_1st' => 'mind',
        'moon_ketu_maternal' => 'relationships',
        'moon_saturn_sorrow' => 'mind',
        'moon_rahu_grandfather' => 'relationships',
        'moon_saturn_venus_water' => 'health',
        'moon_saturn_isolation' => 'relationships',
        'moon_mars_blood' => 'health',
        'moon_mars_blood_conjunct' => 'health',
        'moon_mars_virgo_lagna' => 'health',
        'moon_venus_mars_serious' => 'health',
        'lagnesh_blood_urinary' => 'health',
        'moon_cancer_heart_clot' => 'health',
        'mars_afflicted_blood' => 'health',
        'mars_afflicted_surgery' => 'health',
        'mars_afflicted_pitta' => 'health',
        'mars_afflicted_marrow' => 'health',
        'mars_afflicted_anorectal' => 'health',
        'mars_afflicted_mind' => 'mind',
    ];

    private static function rules(): array
    {
        $trik = [6, 8, 7, 4, 12];
        $malefics = ['Venus', 'Saturn', 'Rahu', 'Ketu'];

        return [
            // ---------------- SUN ----------------
            [
                'sun_lord_afflicted', -2,
                'Surya rules a sensitive bhava and sits in a difficult one alongside malefic company. The classical reading is weakness in the bones, trouble with the eyes, disordered bile and a weak immune response, together with strain in the relationship with the father, recognition withheld by those in authority, a dimmed personal presence, and difficulty settling on a direction in life. Where this produces a persistent sense of despair, treat it as a reason to seek support rather than something to carry quietly',
                ['planet' => 'Sun', 'lord_of' => [1, 4, 6, 8], 'in_house' => $trik, 'with_any' => $malefics],
            ],
            [
                'sun_lagnesh_nodes_head', -2,
                'Surya rules the Lagna, occupies a difficult bhava and is joined by a node. The classical indication is injury or surgery involving the head or skull. Read it as a standing reason for caution rather than a fixed event',
                ['planet' => 'Sun', 'lord_of' => [1], 'in_house' => [6, 4, 8, 7, 12], 'with_any' => ['Rahu', 'Ketu']],
            ],
            [
                'sun_lagnesh_6_moon_venus', -2,
                'Surya rules the Lagna and stands in the 6th with Chandra and Shukra together. The classical reading of this combination is vulnerability of the chest and lungs, pneumonia in particular',
                ['planet' => 'Sun', 'lord_of' => [1], 'in_house' => [6], 'with_all' => ['Moon', 'Venus']],
            ],
            [
                'sun_lagnesh_8_moon_venus', -2,
                'Surya rules the Lagna and stands in the 8th with Chandra and Shukra together. The classical reading is vulnerability of the chest and lungs, and a warning regarding water',
                ['planet' => 'Sun', 'lord_of' => [1], 'in_house' => [8], 'with_all' => ['Moon', 'Venus']],
            ],
            [
                'third_lord_arm_fracture', -1,
                'The lord of the 3rd occupies a difficult bhava in the company of Shani or a node. The classical indication is fracture or injury to the bone of the arm',
                ['lord_of' => [3], 'in_house' => [6, 8, 7, 12], 'with_any' => ['Ketu', 'Rahu', 'Saturn']],
            ],
            [
                'sun_rahu_stomach', -1,
                'Surya rules a difficult bhava and sits with Rahu in the 5th or 6th. The classical reading is infection of the stomach, and a pull toward gambling and similar habits',
                ['planet' => 'Sun', 'lord_of' => [5, 6, 8], 'in_house' => [5, 6], 'with_any' => ['Rahu']],
            ],
            [
                'sun_ketu_spiritual', 0,
                'Surya rules a difficult bhava and sits with Ketu. The classical reading is a stubborn temperament paired with genuine spiritual inclination, and a standing caution regarding head injury through accident',
                ['planet' => 'Sun', 'lord_of' => [5, 6, 8], 'in_house' => [5, 6], 'with_any' => ['Ketu']],
            ],
            [
                'sun_rahu_mercury_nerves', -2,
                'Surya stands with Rahu while Budha occupies a difficult bhava. The classical reading is disturbance of the nervous system, serious trouble in the chest, and psychological strain',
                ['planet' => 'Sun', 'with_any' => ['Rahu'], 'in_house' => [6, 7, 8]],
            ],
            [
                'sun_saturn_trik', -2,
                'Surya and Shani share a difficult bhava. The classical reading is trouble in the nerves, the legs, the teeth, the ears and the hair',
                ['planet' => 'Sun', 'in_house' => [6, 8, 7, 12], 'influenced_by' => ['Saturn']],
            ],
            [
                'sun_mars_fire_injury', -2,
                'Surya rules the Lagna or the 2nd and shares a difficult bhava with Mangala. The classical indication is injury to the eye or forehead by a sharp implement, and harm from fire or electrical current',
                ['planet' => 'Sun', 'lord_of' => [1, 2], 'in_house' => [6, 8, 7, 4], 'influenced_by' => ['Mars']],
            ],
            [
                'sun_venus_reproductive', -2,
                'Surya stands with Shukra in a sensitive bhava in a fiery or watery sign. The classical reading is vulnerability in the reproductive organs, the prostate, the kidneys and the urinary system, and the indication is read as more serious where Shani aspects or a node joins',
                ['planet' => 'Sun', 'in_house' => [6, 8, 7, 1], 'in_sign' => [8, 4, 7, 0], 'with_any' => ['Venus']],
            ],
            [
                'sun_mars_rahu_fire', -2,
                'Surya, Mangala and Rahu come together in a sensitive bhava. The classical reading is harm connected with fire, pronounced aggression, and danger arising from impulse. Where that impulse turns against the self, it should be treated as a reason to seek help early',
                ['planet' => 'Sun', 'in_house' => [1, 6, 4, 8, 7, 12], 'with_all' => ['Mars', 'Rahu']],
            ],

            // --- alternate branches the source states with "OR" ---
            [
                'sun_rahu_mercury_low_degree', -2,
                'Surya, Rahu and Budha stand together with Surya at a low degree in its sign. The classical reading is disturbance of the nervous system, serious trouble in the chest, and psychological strain',
                ['planet' => 'Sun', 'with_all' => ['Rahu', 'Mercury'], 'degree_below' => 10],
            ],
            [
                'sun_12_mars_fire_injury', -2,
                'Surya occupies the 12th in the company of Mangala. The classical indication is injury to the eye or forehead by a sharp implement, and harm from fire or electrical current',
                ['planet' => 'Sun', 'in_house' => [12], 'influenced_by' => ['Mars']],
            ],
            [
                'mars_venus_reproductive', -2,
                'Mangala stands with Shukra in a sensitive bhava in a fiery or watery sign. The classical reading is vulnerability in the reproductive organs, the prostate, the kidneys and the urinary system',
                ['planet' => 'Mars', 'in_house' => [6, 8, 7, 1], 'in_sign' => [8, 4, 7, 0], 'with_any' => ['Venus']],
            ],
            [
                'sun_venus_reproductive_severe', -2,
                'Surya and Shukra stand together in a sensitive fiery or watery sign with Shani aspecting or a node joining. The source reads this combination as markedly more serious for the reproductive and urinary systems, and it warrants ordinary medical vigilance rather than alarm',
                [
                    'planet' => 'Sun',
                    'in_house' => [6, 8, 7, 1],
                    'in_sign' => [8, 4, 7, 0],
                    'with_any' => ['Venus'],
                    'aspected_by' => ['Saturn'],
                ],
            ],

            // ---------------- MOON ----------------
            [
                'moon_1st_fickle', -1,
                'Chandra occupies the Lagna, giving a changeable and restless temperament that settles slowly',
                ['planet' => 'Moon', 'in_house' => [1], 'not_in_house' => []],
            ],
            [
                'moon_2_6_10_unstable', -1,
                'Chandra occupies the 2nd, 6th or 10th, which unsettles the temperament and makes mental quiet harder to hold. In the company of Rahu, Ketu, Shani or Shukra the mind carries real distress',
                ['planet' => 'Moon', 'in_house' => [2, 6, 10], 'with_any' => ['Rahu', 'Ketu', 'Saturn', 'Venus']],
            ],
            [
                'fourth_lord_chest', -2,
                'The lord of the 4th occupies the 2nd, 6th, 8th or 10th with Rahu, Ketu, Shani or Shukra. The classical reading is vulnerability in the chest, the lungs and the blood',
                ['lord_of' => [4], 'in_house' => [2, 6, 8, 10], 'with_any' => ['Rahu', 'Ketu', 'Saturn', 'Venus']],
            ],
            [
                'sixth_lord_blood', -2,
                'The lord of the 6th occupies the 8th, 10th or 12th with Shani or a node. The classical reading is impurity in the blood, hormonal imbalance, and an effect upon the mind',
                ['lord_of' => [6], 'in_house' => [8, 10, 12], 'with_any' => ['Saturn', 'Rahu', 'Ketu']],
            ],
            [
                'moon_12_fire_water', -2,
                'Chandra occupies the 12th in a fiery or watery sign. The classical reading is expenditure on treatment, disturbed sleep, and a temperament that runs emotional and unsettled',
                ['planet' => 'Moon', 'in_house' => [12], 'in_sign' => [0, 4, 8, 7]],
            ],
            [
                'moon_aries_lagna_1st', 2,
                'Chandra occupies the Lagna in Mesha. The supplied reading treats every outcome of this placement as auspicious',
                ['planet' => 'Moon', 'in_house' => [1], 'lagna_sign' => 0],
            ],
            [
                'moon_ketu_maternal', -1,
                'Chandra and Ketu stand together in the 2nd, 6th, 8th or 10th. The classical reading is continuing hardship for the maternal grandmother and for the mother-in-law',
                ['planet' => 'Moon', 'in_house' => [6, 8, 2, 10], 'with_any' => ['Ketu']],
            ],
            [
                'moon_saturn_sorrow', -2,
                'Chandra and Shani share the 2nd, 6th or 8th. The classical reading is a mind that carries sadness, distress caused through the mother-in-law, and swelling in the body',
                ['planet' => 'Moon', 'in_house' => [2, 6, 8], 'with_any' => ['Saturn']],
            ],
            [
                'moon_rahu_grandfather', -1,
                'Chandra and Rahu stand together in a sensitive bhava. The classical reading is suffering borne by the paternal grandfather',
                ['planet' => 'Moon', 'in_house' => [6, 8, 12, 2, 10], 'with_any' => ['Rahu']],
            ],
            [
                'moon_saturn_venus_water', -2,
                'Chandra stands with Shani and Shukra in a sensitive bhava. The classical reading is imbalance of the water element, producing swelling in the body and an unsettled mind',
                [
                    'planet' => 'Moon',
                    'in_house' => [2, 6, 8, 10, 12],
                    'with_all' => ['Saturn', 'Venus'],
                    // Mesha, Kanya, Vrischika, Dhanu, Kumbha, Makara
                    'in_sign' => [0, 5, 7, 8, 10, 9],
                ],
            ],
            [
                'moon_saturn_isolation', -2,
                'Chandra rules a difficult bhava and is joined or aspected by Shani. The classical reading is loneliness, and a sense of being let down by those around you',
                ['planet' => 'Moon', 'lord_of' => [6, 8, 12, 10, 4, 2], 'influenced_by' => ['Saturn']],
            ],
            [
                'moon_mars_blood', -2,
                'Chandra is joined or aspected by Mangala from a sensitive bhava. The classical reading is disorder of the blood and the urinary system',
                [
                    'planet' => 'Moon',
                    'in_house' => [6, 10, 2, 8, 12],
                    'aspected_by' => ['Mars'],
                    // The source restricts this to Mesha, Vrischika, Dhanu, Simha or Kanya.
                    'in_sign' => [0, 7, 8, 4, 5],
                ],
            ],
            [
                'moon_mars_blood_conjunct', -2,
                'Chandra and Mangala stand together in a sensitive bhava. The classical reading is disorder of the blood and the urinary system',
                [
                    'planet' => 'Moon',
                    'in_house' => [6, 10, 2, 8, 12],
                    'with_any' => ['Mars'],
                    'in_sign' => [0, 7, 8, 4, 5],
                ],
            ],
            [
                'moon_mars_virgo_lagna', -2,
                'Chandra and Mangala are conjoined in a sensitive bhava for a Kanya ascendant. The classical reading is mental strain and vulnerability in the chest and lungs; where Shani aspects, the blood, urine and prostate are also implicated',
                ['planet' => 'Moon', 'lagna_sign' => 5, 'in_house' => [6, 8, 7, 10, 2, 1], 'with_any' => ['Mars']],
            ],
            [
                'moon_venus_mars_serious', -2,
                'Chandra stands with Shukra and Mangala, lords of difficult bhavas, in a sensitive house. The supplied text reads this as a serious combination for the blood, the urinary tract, the prostate, the uterus and the ovaries. It is a reason for ordinary medical vigilance, not a diagnosis',
                [
                    'planet' => 'Moon',
                    'in_house' => [2, 6, 7, 8, 12],
                    'with_all' => ['Venus', 'Mars'],
                    // The source requires the two to own dusthanas.
                    'companion_lord_of' => ['planets' => ['Venus', 'Mars'], 'houses' => [6, 8, 12]],
                ],
            ],

            // ---------------- MARS ----------------
            [
                'mars_afflicted_blood', -2,
                'Mangala, significator of the blood, stands afflicted in a Trik bhava. The classical reading groups the vulnerabilities as sudden swings in blood pressure, impurity or infection of the blood, and the risk of internal bleeding',
                ['planet' => 'Mars', 'in_house' => [6, 8, 12], 'with_any' => ['Saturn', 'Rahu', 'Ketu', 'Sun']],
            ],
            [
                'mars_afflicted_surgery', -2,
                'Mangala governs sharp implements, metal and fire, and stands afflicted in a Trik bhava. The classical reading is a raised likelihood of surgery, of burns from fire, electricity or acid, and of fracture or torn muscle through accident',
                ['planet' => 'Mars', 'in_house' => [6, 8, 12], 'in_sign' => [1, 3, 5, 6, 9, 10]],
            ],
            [
                'mars_afflicted_pitta', -1,
                'Mangala rules the digestive fire and the pitta dosha, and stands afflicted in a Trik bhava. The classical reading is ulcer and hyperacidity, inflammation of the liver or gallstones, and inflammation within the intestines',
                ['planet' => 'Mars', 'in_house' => [6, 8, 12], 'aspected_by' => ['Saturn', 'Rahu']],
            ],
            [
                'mars_afflicted_anorectal', -2,
                'Mangala stands afflicted in the 6th or 8th. The classical reading is vulnerability to piles, fistula or fissure, inflammation of the reproductive organs, and in women heavy menstruation with severe abdominal pain',
                ['planet' => 'Mars', 'in_house' => [6, 8], 'with_any' => ['Saturn', 'Rahu', 'Ketu', 'Venus']],
            ],
            [
                'lagnesh_blood_urinary', -2,
                'The lord of the Lagna occupies the 2nd, 6th, 8th or 12th in the company of Mangala, Rahu, Shukra or Ketu. The classical reading is disorder of the blood, the urinary system, the kidneys and the brain',
                ['lord_of' => [1], 'in_house' => [2, 6, 8, 12], 'with_any' => ['Mars', 'Rahu', 'Venus', 'Ketu']],
            ],
            [
                'moon_cancer_heart_clot', -2,
                'Karka falls on a sensitive bhava, Chandra is afflicted by malefic company, and the lord of the 4th occupies a dusthana. The source reads this combination together as indicating the lungs, the chest, and the risk of a clot affecting the heart. It is a reason for ordinary cardiac vigilance, not a diagnosis',
                [
                    'planet' => 'Moon',
                    'sign_in_house' => ['sign' => 3, 'house' => [6, 8, 2, 10]],
                    'with_any' => ['Rahu', 'Ketu', 'Saturn', 'Venus'],
                    'lord_in_house' => [
                        ['lord_of' => [4], 'in_house' => [6, 8, 12]],
                    ],
                ],
            ],
            [
                'mars_afflicted_marrow', -2,
                'Mangala, significator of the bone marrow and the muscles, stands afflicted in a Trik bhava. The classical reading is wasting of the muscles, severe cramp or sharp muscular pain, and abnormality in the bone marrow obstructing the production of blood cells',
                ['planet' => 'Mars', 'in_house' => [6, 8, 12], 'with_any' => ['Saturn', 'Rahu', 'Ketu', 'Sun', 'Venus']],
            ],
            [
                'mars_afflicted_mind', -2,
                'Mangala stands afflicted in the 12th. The classical reading is difficulty controlling impulse, aggression and high mental strain, together with insomnia, nightmares and irrational fear',
                ['planet' => 'Mars', 'in_house' => [12]],
            ],
        ];
    }
}
