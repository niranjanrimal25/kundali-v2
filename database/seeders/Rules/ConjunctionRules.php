<?php

namespace Database\Seeders\Rules;

use App\Services\Astrology\Interpretation\RuleRepository;

/**
 * Yuti — two grahas sharing a bhava.
 * Every unordered pair of the nine grahas = 36 fragments.
 *
 * Without this layer two occupants of one house read as two unrelated
 * sentences. A conjunction is not two placements; it is a third thing.
 *
 * Keys are alphabetically ordered pairs ("Jupiter+Saturn") so lookup
 * never depends on which graha the generator happens to reach first.
 */
class ConjunctionRules
{
    public static function all(): array
    {
        $rows = [];

        foreach (self::map() as $pair => [$polarity, $text]) {
            [$a, $b] = explode('+', $pair);
            $key = RuleRepository::conjunctionKey($a, $b);

            $rows[] = [
                'condition_type' => 'conjunction',
                'condition_key' => $key,
                'section' => 'modifier',
                'polarity' => $polarity,
                'weight' => 75,
                'text' => $text,
                'conditions' => null,
            ];
        }

        return $rows;
    }

    private static function map(): array
    {
        return [
            // ---- Sun ----
            'Sun+Moon' => [-1, 'Surya and Chandra together mark a new-moon birth. Will and feeling pull in the same direction, which concentrates the personality but leaves little internal counterweight when judgement goes wrong'],
            'Sun+Mars' => [1, 'Surya with Mangala produces considerable force of personality and physical courage. It also produces a temper that arrives faster than the judgement meant to govern it'],
            'Sun+Mercury' => [1, 'Surya with Budha forms Budha-Aditya yoga, giving intelligence, articulate authority and administrative capability. Mercury is usually combust here, so the sharpness is real but its outward expression is muted'],
            'Sun+Jupiter' => [2, 'Surya with Guru joins authority to principle. This is a genuinely fortunate combination, giving ethical leadership, respect from elders and a conscience that governs ambition'],
            'Sun+Venus' => [0, 'Surya with Shukra gives artistic sensibility and charm alongside authority. Venus is often combust here, which complicates relationships: affection is felt strongly but expressed awkwardly'],
            'Sun+Saturn' => [-2, 'Surya with Shani sets authority against limitation. This is the classic father-difficulty combination — recognition is delayed, effort exceeds reward for years, and self-worth is built the hard way. What it eventually produces is durable, but the cost is paid first'],
            'Sun+Rahu' => [-1, 'Surya with Rahu produces inflated ambition and a hunger for status that outpaces actual standing. It can bring sudden prominence, and equally sudden exposure'],
            'Sun+Ketu' => [-1, 'Surya with Ketu undercuts confidence in one\'s own authority. There is real capability alongside a persistent sense of not deserving the position held'],

            // ---- Moon ----
            'Mars+Moon' => [0, 'Chandra with Mangala forms Chandra-Mangala yoga, which is a genuine wealth combination. It also makes the emotional nature volatile: feeling converts to action, and often to anger, without an intervening pause'],
            'Mercury+Moon' => [1, 'Chandra with Budha gives an articulate, quick and psychologically perceptive mind. You understand people by reading them rather than by being told'],
            'Jupiter+Moon' => [2, 'Chandra with Guru forms Gaja Kesari yoga, one of the most benefic combinations available. It gives wisdom, emotional generosity, good reputation and protection in difficulty'],
            'Moon+Venus' => [1, 'Chandra with Shukra gives refinement, artistic feeling and a strong need for beauty and affection in daily surroundings'],
            'Moon+Saturn' => [-2, 'Chandra with Shani is the combination most associated with persistent low mood. Emotion is restricted, warmth is hard to express, and early life often lacked comfort. It also gives real emotional endurance — but that endurance is learned under pressure'],
            'Moon+Rahu' => [-2, 'Chandra with Rahu creates Grahan yoga, disturbing the mind. Anxiety, restlessness, unusual imagination and vulnerability to illusion. Mental health deserves deliberate attention with this placement'],
            'Ketu+Moon' => [-1, 'Chandra with Ketu produces emotional detachment and a recurring sense of estrangement, alongside genuine intuitive and spiritual sensitivity'],

            // ---- Mars ----
            'Mars+Mercury' => [0, 'Mangala with Budha gives sharp, incisive, argumentative intelligence. Debate is won readily; the wounds left behind are not always intended'],
            'Jupiter+Mars' => [1, 'Mangala with Guru joins energy to principle, producing decisive ethical action. The risk is righteousness: certainty that one\'s aggression is justified'],
            'Mars+Venus' => [0, 'Mangala with Shukra gives strong passion and magnetism, with relationships marked by intensity rather than calm. Attraction is immediate and conflict is frequent'],
            'Mars+Saturn' => [-2, 'Mangala with Shani is a genuinely difficult combination: acceleration and braking applied together. It produces frustration, suppressed anger and accidents or injuries. Handled consciously it gives exceptional endurance, but it must be handled consciously'],
            'Mars+Rahu' => [-2, 'Mangala with Rahu forms Angarak yoga, giving explosive energy, recklessness and risk of accident or violent dispute. Deliberate restraint is not optional here'],
            'Ketu+Mars' => [-1, 'Mangala with Ketu gives sudden, unpredictable bursts of force and considerable technical or surgical skill, alongside a tendency to act without warning'],

            // ---- Mercury ----
            'Jupiter+Mercury' => [2, 'Budha with Guru joins detail to breadth, producing genuine scholarship. Teaching, writing, law and advisory work are all well supported'],
            'Mercury+Venus' => [1, 'Budha with Shukra gives artistic articulacy — skill in design, music, poetry, and diplomacy. Charm and intelligence reinforce each other'],
            'Mercury+Saturn' => [0, 'Budha with Shani produces slow, thorough, systematic thinking. Speech is sparse and considered. Learning is laborious but what is learned is retained permanently'],
            'Mercury+Rahu' => [0, 'Budha with Rahu gives exceptional cleverness and facility with technology and unconventional fields, alongside a real capacity for deception that cuts both ways'],
            'Ketu+Mercury' => [0, 'Budha with Ketu produces intuitive rather than analytical intelligence. Conclusions arrive whole, without visible reasoning, and are usually right'],

            // ---- Jupiter ----
            'Jupiter+Venus' => [1, 'Guru with Shukra joins the two benefics, giving refinement, prosperity and good judgement in matters of love and money. The shared weakness is indulgence'],
            'Jupiter+Saturn' => [0, 'Guru with Shani sets expansion against contraction. Growth is slow, tested and permanent. Optimism is rationed, but what survives the rationing is real'],
            'Jupiter+Rahu' => [-1, 'Guru with Rahu forms Guru-Chandal yoga, distorting judgement. Unconventional belief, brilliance mixed with poor ethical footing, and teachers who disappoint'],
            'Jupiter+Ketu' => [1, 'Guru with Ketu turns wisdom inward toward renunciation. Genuine spiritual capacity, with diminished interest in worldly reward'],

            // ---- Venus ----
            'Saturn+Venus' => [-1, 'Shukra with Shani delays and formalises affection. Marriage comes late or to someone older, and love is expressed through duty and provision rather than warmth'],
            'Rahu+Venus' => [-1, 'Shukra with Rahu produces intense, unconventional attraction and relationships that cross ordinary boundaries. Excess in pleasure is the standing risk'],
            'Ketu+Venus' => [-1, 'Shukra with Ketu brings detachment into love. Relationships carry a quality of unfinished business, and satisfaction is elusive even when circumstances are good'],

            // ---- Saturn ----
            'Rahu+Saturn' => [-2, 'Shani with Rahu is a heavy combination, producing prolonged obstruction, isolation and ambition pursued through difficult means. It also confers formidable resilience'],
            'Ketu+Saturn' => [-1, 'Shani with Ketu gives austerity, solitude and detachment from worldly reward. It suits ascetic or deeply specialised work and suits ordinary social life poorly'],

            // ---- Nodes ----
            'Ketu+Rahu' => [-2, 'Rahu and Ketu together in one bhava is unusual and marks that house as the axis of the life. Its affairs are subject to extremes of grasping and renunciation in alternation'],
        ];
    }
}
