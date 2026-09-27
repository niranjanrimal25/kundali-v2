<?php

namespace Database\Seeders\Rules;

/**
 * Yogas, doshas and Saturn transits.
 *
 * Keys here must match exactly what YogaDetector and DoshaDetector
 * emit, so a rule can never be written for a combination the engine
 * cannot detect.
 *
 * Yoga fragments vary by detected strength, because a Pancha
 * Mahapurusha formed by an exalted graha is not the same claim as one
 * formed by a merely own-sign graha, and saying so honestly is the
 * difference between a reading and a horoscope column.
 */
class YogaRules
{
    public static function all(): array
    {
        $rows = [];

        foreach (self::yogas() as $key => $variants) {
            foreach ($variants as $strength => [$polarity, $text]) {
                $rows[] = [
                    'condition_type' => 'yoga',
                    'condition_key' => "{$key}:{$strength}",
                    'section' => 'yogas',
                    'polarity' => $polarity,
                    'weight' => 86,
                    'text' => $text,
                    'conditions' => null,
                ];
            }
        }

        foreach (self::doshas() as $key => [$polarity, $text]) {
            $rows[] = [
                'condition_type' => 'dosha',
                'condition_key' => $key,
                'section' => 'yogas',
                'polarity' => $polarity,
                'weight' => 86,
                'text' => $text,
                'conditions' => null,
            ];
        }

        foreach (self::transits() as $key => [$polarity, $text]) {
            $rows[] = [
                'condition_type' => 'transit',
                'condition_key' => $key,
                'section' => 'transits',
                'polarity' => $polarity,
                'weight' => 84,
                'text' => $text,
                'conditions' => null,
            ];
        }

        return $rows;
    }

    private static function yogas(): array
    {
        return [
            'panchamahapurusha_ruchaka' => [
                'strong' => [2, 'Ruchaka Yoga is formed, and formed well. This is one of the five Mahapurusha combinations, and it gives physical courage, command and a capacity to lead from the front that other people notice immediately. The accompanying difficulty is a temper that does not wait for permission'],
                'moderate' => [2, 'Ruchaka Yoga is formed. It gives real courage, physical vigour and natural authority in a crisis, alongside a tendency to act before deliberating'],
            ],
            'panchamahapurusha_bhadra' => [
                'strong' => [2, 'Bhadra Yoga is formed, and formed well. Intelligence, articulacy and commercial judgement are exceptional here, and learning comes with unusual ease'],
                'moderate' => [2, 'Bhadra Yoga is formed. It gives a sharp, adaptable intellect and genuine skill in speech, writing and trade'],
            ],
            'panchamahapurusha_hamsa' => [
                'strong' => [2, 'Hamsa Yoga is formed, and formed well. This is the most benefic of the five, giving wisdom, moral authority and a life that others tend to seek guidance from'],
                'moderate' => [2, 'Hamsa Yoga is formed. It gives sound judgement, a principled temperament and respect that is earned rather than demanded'],
            ],
            'panchamahapurusha_malavya' => [
                'strong' => [2, 'Malavya Yoga is formed, and formed well. Refinement, artistic capability, personal attractiveness and material comfort are all strongly indicated'],
                'moderate' => [2, 'Malavya Yoga is formed. It gives aesthetic sense, social grace and comfortable circumstances'],
            ],
            'panchamahapurusha_sasa' => [
                'strong' => [2, 'Sasa Yoga is formed, and formed well. It gives exceptional endurance, disciplined ambition and authority over people or institutions, though the rewards arrive later than for most'],
                'moderate' => [2, 'Sasa Yoga is formed. It gives patience, organisational capability and a standing built slowly and held permanently'],
            ],
            'gaja_kesari' => [
                'strong' => [2, 'Gaja Kesari Yoga is formed strongly. Guru\'s position relative to Chandra gives intelligence, good reputation and a protective quality that repairs difficulty elsewhere in the chart'],
                'moderate' => [2, 'Gaja Kesari Yoga is formed. It gives sound judgement, a generally good name among others, and a measure of protection when circumstances turn'],
            ],
            'budha_aditya' => [
                'moderate' => [1, 'Budha-Aditya Yoga is formed, joining intelligence to authority. It favours administration, analysis and any work where being articulate is the advantage'],
                'qualified' => [0, 'Budha-Aditya Yoga is formed, but Budha is combust — burnt by proximity to Surya. The intelligence is genuine; its outward expression is muted, and you are routinely underestimated'],
            ],
            'chandra_mangala' => [
                'moderate' => [1, 'Chandra-Mangala Yoga is formed. Classically read as a wealth combination, it also makes the emotional nature volatile: feeling converts into action, and sometimes into anger, without an intervening pause'],
            ],
            'kemadruma' => [
                'moderate' => [-1, 'Kemadruma Yoga is present: Chandra stands without support from the houses on either side of it. This inclines toward periods of isolation and a sense of having to manage alone. It is much reduced when the Moon is otherwise well placed, which is worth weighing before treating it as a verdict'],
            ],
            'vipareeta_raja' => [
                'moderate' => [1, 'A Vipareeta Raja Yoga is present. One lord of difficulty sits in another house of difficulty, and the classical reading is that these cancel rather than compound: hardship arrives, and you come out of it better positioned than you went in'],
            ],
            'raja_yoga' => [
                'strong' => [2, 'A Raja Yoga is formed by the association of a kendra lord with a trikona lord. This is the classical signature of real advancement in life, and it tends to express through whichever bhava the two occupy'],
                'moderate' => [2, 'A Raja Yoga is formed, joining an angular lord to a trinal one. It supports genuine rise in standing, usually through a combination of capability and timely opportunity'],
            ],
            'dhana_yoga' => [
                'moderate' => [2, 'A Dhana Yoga is formed by the meeting of the 2nd and 11th lords. Accumulation of wealth is supported, particularly through the affairs of whichever bhava they share'],
            ],
        ];
    }

    private static function doshas(): array
    {
        return [
            'mangal_dosha' => [-1, 'Mangal Dosha is present. Mangala occupies one of the positions classically held to strain marriage, bringing friction, impatience and a tendency for disagreements to escalate faster than either party intends. It is worth stating plainly that this is the single most over-dramatised feature in popular astrology: it describes a temperament to manage, not a sentence on the marriage'],
            'mangal_dosha_mitigated' => [0, 'The dosha is therefore technically present but cancelled. Classical opinion treats a cancelled Mangal Dosha as substantially neutralised, and it should not be treated as an obstacle to marriage'],
            'kaal_sarpa' => [-1, 'Kaal Sarpa Yoga is present: every graha falls within the arc between Rahu and Ketu. It is associated with a life of pronounced phases — long obstruction followed by sudden release — and with achievement that arrives later and more abruptly than expected. Its severity is routinely overstated'],
            'grahan_dosha_sun' => [-1, 'Grahan Dosha affects Surya, which is conjoined with a node. Confidence and the relationship with the father carry a shadow, and recognition tends to arrive in a distorted form — either less than deserved or for the wrong thing'],
            'grahan_dosha_moon' => [-2, 'Grahan Dosha affects Chandra, which is conjoined with a node. This is the more consequential of the two, unsettling the mind and making anxiety, vivid imagination and emotional volatility recurring features. Deliberate mental discipline repays the effort here more than almost any other measure'],
            'kemadruma_affliction' => [-2, 'Chandra is both weakened by sign and unsupported by neighbouring houses. Emotional resilience is genuinely low, and periods of isolation land harder than they would for most people. This is the single feature of this chart most worth actively managing'],
        ];
    }

    private static function transits(): array
    {
        return [
            'sade_sati_first' => [-1, 'You are in the first phase of Sade Sati, with transiting Shani in the 12th from your natal Chandra. This phase typically brings rising expenditure, disturbed sleep and a sense of losing ground before anything visible has actually gone wrong. It is the setup rather than the event'],
            'sade_sati_peak' => [-2, 'You are in the peak phase of Sade Sati, with transiting Shani over your natal Chandra. This is the most demanding stretch of the seven-and-a-half years: energy is low, responsibility is high, and matters long deferred present themselves for settlement. It is genuinely hard, and it is also finite and formative. What is built now holds'],
            'sade_sati_final' => [-1, 'You are in the closing phase of Sade Sati, with transiting Shani in the 2nd from your natal Chandra. Financial and family pressures dominate, but the weight is lifting. This phase resolves rather than deepens'],
            'kantaka_shani' => [-1, 'Transiting Shani stands in the 4th from your natal Chandra, a placement called Kantaka Shani. It presses on home, property and inner peace, and tends to produce restlessness with where and how you are living'],
            'ashtama_shani' => [-2, 'Transiting Shani stands in the 8th from your natal Chandra, called Ashtama Shani. This is a demanding transit affecting health, confidence and finances. Caution with risk and attention to health repay themselves during it'],
        ];
    }
}
