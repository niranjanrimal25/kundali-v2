<?php

namespace Database\Seeders\Rules;

/**
 * Digbala — directional strength, per graha.
 *
 * Only seven grahas have a digbala house; Rahu and Ketu have none, being
 * shadow points rather than bodies. Four strength bands each = 28 rules.
 *
 * Jupiter/Mercury are strongest in the East (Lagna), Sun/Mars in the
 * South (10th), Saturn in the West (7th), Moon/Venus in the North (4th).
 *
 * This is the layer the user's original example asked for by name:
 * house, sign and direction combining into one causal statement.
 *
 * Written to follow "It ...".
 */
class DigbalaRules
{
    public static function all(): array
    {
        $rows = [];

        foreach (self::map() as $planet => $bands) {
            foreach ($bands as $band => [$polarity, $weight, $text]) {
                $rows[] = [
                    'condition_type' => 'digbala_planet',
                    'condition_key' => "{$planet}:{$band}",
                    'section' => 'modifier',
                    'polarity' => $polarity,
                    'weight' => $weight,
                    'text' => $text,
                    'conditions' => null,
                ];
            }
        }

        return $rows;
    }

    private static function map(): array
    {
        return [
            'Sun' => [
                'full' => [2, 80, 'holds full directional strength (Digbala) in the south, the quarter of visible achievement, which is the single best position it can occupy for career and public standing'],
                'strong' => [1, 65, 'holds good directional strength (Digbala) toward the south, lending steadiness to matters of status and authority'],
                'weak' => [-1, 65, 'is directionally weak (little Digbala), so recognition has to be pursued rather than granted'],
                'powerless' => [-2, 80, 'is directionally powerless (no Digbala) in the fourth, the quarter of private life, which is the worst position for it. Authority and visibility are genuinely hard to establish, and effort spent seeking them returns little'],
            ],
            'Moon' => [
                'full' => [2, 80, 'holds full directional strength (Digbala) in the north, the quarter of home and inner life, giving deep emotional security and real domestic contentment'],
                'strong' => [1, 65, 'holds good directional strength (Digbala) toward the north, supporting emotional steadiness'],
                'weak' => [-1, 65, 'is directionally weak (little Digbala), so peace of mind depends more on circumstances than it should'],
                'powerless' => [-2, 80, 'is directionally powerless (no Digbala) in the tenth, the quarter of public exposure, which drains it. Emotional depletion through overexposure and overwork is the specific risk'],
            ],
            'Mars' => [
                'full' => [2, 80, 'holds full directional strength (Digbala) in the south, the quarter of action, which is the best position it can occupy. Energy is decisive and effective rather than merely restless'],
                'strong' => [1, 65, 'holds good directional strength (Digbala) toward the south, giving effective and well-aimed drive'],
                'weak' => [-1, 65, 'is directionally weak (little Digbala), so effort produces less than it should and frustration accumulates'],
                'powerless' => [-2, 80, 'is directionally powerless (no Digbala) in the fourth, where force has nothing to act on. Energy turns inward and emerges as domestic friction rather than achievement'],
            ],
            'Mercury' => [
                'full' => [2, 80, 'holds full directional strength (Digbala) in the east, at the Lagna itself, which is the best position for it. Intelligence, articulacy and quickness of mind are immediately apparent to anyone who meets you'],
                'strong' => [1, 65, 'holds good directional strength (Digbala) toward the east, supporting clear thinking and expression'],
                'weak' => [-1, 65, 'is directionally weak (little Digbala), so communication requires more deliberate effort to land'],
                'powerless' => [-2, 80, 'is directionally powerless (no Digbala) in the seventh, so your reasoning is least effective precisely where it matters most: in negotiation and in dealing with others'],
            ],
            'Jupiter' => [
                'full' => [2, 80, 'holds full directional strength (Digbala) in the east, at the Lagna itself, the best position available to it. Its protective and expansive influence reaches the whole chart, which substantially offsets difficulty elsewhere'],
                'strong' => [1, 65, 'holds good directional strength (Digbala) toward the east, supporting good judgement and timely protection'],
                'weak' => [-1, 65, 'is directionally weak (little Digbala), so its protection is present but thin'],
                'powerless' => [-2, 80, 'is directionally powerless (no Digbala) in the seventh, so the chart\'s principal protector is at its least effective. Guidance arrives late, and difficulty is met with less cushioning than most people have'],
            ],
            'Venus' => [
                'full' => [2, 80, 'holds full directional strength (Digbala) in the north, the quarter of comfort and private happiness, which is the best position for it. Domestic life, property and affection are genuinely well supported'],
                'strong' => [1, 65, 'holds good directional strength (Digbala) toward the north, supporting comfort and harmony at home'],
                'weak' => [-1, 65, 'is directionally weak (little Digbala), so comfort and affection require more maintenance than they should'],
                'powerless' => [-2, 80, 'is directionally powerless (no Digbala) in the tenth, so pleasure and relationship are subordinated to career. Private life is the thing that gets sacrificed'],
            ],
            'Saturn' => [
                'full' => [2, 80, 'holds full directional strength (Digbala) in the west, the quarter of others and of partnership, which is the best position it can occupy. It gives exceptional endurance and makes its delays productive rather than merely punishing'],
                'strong' => [1, 65, 'holds good directional strength (Digbala) toward the west, so its discipline works for you rather than against you'],
                'weak' => [-1, 65, 'is directionally weak (little Digbala), so delay is less purposeful and harder to turn to account'],
                'powerless' => [-2, 80, 'is directionally powerless (no Digbala) at the Lagna, where restriction falls directly on the self. Vitality, confidence and the body itself carry the weight of its delays'],
            ],
        ];
    }
}
