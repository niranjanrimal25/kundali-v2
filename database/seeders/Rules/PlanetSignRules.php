<?php

namespace Database\Seeders\Rules;

/**
 * Graha x Rashi — the manner in which a planet acts.
 * 9 grahas x 12 signs = 108 fragments.
 *
 * House placement says WHERE a graha operates; sign placement says HOW.
 * These fragments are written to follow the graha's name, e.g.
 * "Surya here is ..." / "... and acts ...".
 */
class PlanetSignRules
{
    public static function all(): array
    {
        $rows = [];

        foreach (self::map() as $planet => $signs) {
            foreach ($signs as $sign => [$polarity, $text]) {
                $rows[] = [
                    'condition_type' => 'planet_sign',
                    'condition_key' => "{$planet}:{$sign}",
                    'section' => 'modifier',
                    'polarity' => $polarity,
                    'weight' => 65,
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
                0 => [2, 'exalted in Aries, where authority is exercised directly and courage is not in question. Leadership is instinctive rather than learned'],
                1 => [0, 'in Taurus, giving a steady, materially grounded authority that asserts itself slowly but does not retreat'],
                2 => [0, 'in Gemini, expressing authority through words, analysis and versatility rather than command'],
                3 => [0, 'in Cancer, where authority is exercised protectively and self-worth is bound up with family and belonging'],
                4 => [2, 'in its own sign Leo, where it is unobstructed. Dignity, confidence and natural command are fully available to you'],
                5 => [0, 'in Virgo, producing a precise, service-minded authority that earns respect through competence rather than presence'],
                6 => [-2, 'debilitated in Libra, where self-assertion is traded away for harmony. Authority must be consciously reclaimed, as the instinct is to defer'],
                7 => [0, 'in Scorpio, giving a concentrated, private authority and considerable will held beneath the surface'],
                8 => [1, 'in Sagittarius, joining authority to principle. You lead best when the cause is one you believe in'],
                9 => [-1, 'in Capricorn, where authority is bound to duty and recognition arrives only after long service'],
                10 => [-1, 'in Aquarius, uneasy with hierarchy. You resist being led and are ambivalent about leading'],
                11 => [0, 'in Pisces, softening authority into compassion. Power is exercised reluctantly and often given away'],
            ],
            'Moon' => [
                0 => [0, 'in Aries, giving quick, hot emotional reactions that rise and subside rapidly. Feelings are acted on before they are examined'],
                1 => [2, 'exalted in Taurus, the most settled placement available to it. Emotional steadiness, contentment and a calm inner base'],
                2 => [0, 'in Gemini, producing a restless, verbal emotional life. Feelings are talked through rather than sat with'],
                3 => [2, 'in its own sign Cancer, fully at home. Emotional depth, strong memory and powerful protective instincts'],
                4 => [1, 'in Leo, giving warm, generous feeling that needs to be seen and acknowledged to stay healthy'],
                5 => [-1, 'in Virgo, where feeling is analysed rather than felt. Anxiety is the characteristic emotional weather'],
                6 => [0, 'in Libra, needing companionship to feel settled. Solitude is tolerated poorly'],
                7 => [-2, 'debilitated in Scorpio, the hardest placement for the mind. Emotions run deep, turn inward and are slow to release injury. This is the placement most in need of deliberate emotional discipline'],
                8 => [1, 'in Sagittarius, giving an optimistic, freedom-loving emotional nature that recovers quickly from setback'],
                9 => [-1, 'in Capricorn, where feeling is controlled and expressed sparingly. Others read reserve as coldness'],
                10 => [0, 'in Aquarius, producing detached, observational emotion. You understand feeling better than you inhabit it'],
                11 => [1, 'in Pisces, giving deep compassion and porous emotional boundaries. You absorb the mood of whoever is near you'],
            ],
            'Mars' => [
                0 => [2, 'in its own sign Aries, direct and unimpeded. Energy is abundant, courage immediate, and patience the thing in short supply'],
                1 => [0, 'in Taurus, giving slow, stubborn, enduring force. Anger is rare but long-held once roused'],
                2 => [0, 'in Gemini, turning aggression into argument. Conflict is verbal and quick rather than physical'],
                3 => [-2, 'debilitated in Cancer, where anger goes underground. Emotional resentment replaces direct confrontation, which is the harder pattern to unwind'],
                4 => [1, 'in Leo, giving confident, visible, and rather theatrical force. You fight openly and expect to be admired for it'],
                5 => [0, 'in Virgo, expressing energy through precision and criticism rather than confrontation'],
                6 => [-1, 'in Libra, where aggression is diluted by the need for approval. Conflict is avoided until it can no longer be'],
                7 => [2, 'in its own sign Scorpio, concentrated and formidable. Will is sustained, strategy patient, and opposition outlasted'],
                8 => [1, 'in Sagittarius, giving energetic conviction. You fight for principles and enjoy the fight'],
                9 => [2, 'exalted in Capricorn, the most disciplined placement available. Energy is organised, sustained and directed at long objectives'],
                10 => [0, 'in Aquarius, giving unconventional and occasionally contrarian energy. You resist orders on principle'],
                11 => [-1, 'in Pisces, where drive is diffuse and hard to sustain. Motivation depends on mood'],
            ],
            'Mercury' => [
                0 => [0, 'in Aries, giving rapid, decisive thinking that reaches conclusions before the evidence is complete'],
                1 => [0, 'in Taurus, producing slow, practical, retentive thought. You are hard to convince and harder to unconvince'],
                2 => [2, 'in its own sign Gemini, quick, curious and articulate. Learning is easy and explanation effortless'],
                3 => [0, 'in Cancer, where thinking is coloured by feeling. Memory is strong and objectivity is not'],
                4 => [0, 'in Leo, giving confident, authoritative speech that dislikes being corrected'],
                5 => [2, 'exalted in its own sign Virgo, the strongest position available. Analysis, discrimination and attention to detail are exceptional'],
                6 => [1, 'in Libra, producing balanced, diplomatic communication and genuine skill in negotiation'],
                7 => [1, 'in Scorpio, giving penetrating, investigative thought. You want the hidden reason, not the stated one'],
                8 => [-2, 'debilitated in Sagittarius, where the mind grasps the large picture but neglects the detail that undoes it. Conclusions outrun the evidence'],
                9 => [1, 'in Capricorn, giving structured, practical, methodical intelligence suited to long planning'],
                10 => [1, 'in Aquarius, producing original, systems-level thinking that sees patterns others miss'],
                11 => [-1, 'in Pisces, where thought is intuitive rather than logical. Impressions are accurate; the reasoning behind them is not articulable'],
            ],
            'Jupiter' => [
                0 => [1, 'in Aries, giving pioneering optimism and the confidence to begin large things'],
                1 => [1, 'in Taurus, joining wisdom to material sense. Wealth is accumulated ethically and steadily'],
                2 => [-1, 'in Gemini, where breadth of knowledge outruns depth. Much is learned and little is mastered'],
                3 => [2, 'exalted in Cancer, the strongest position available. Wisdom, compassion and genuine benevolence, with real protective power over the house it occupies'],
                4 => [1, 'in Leo, giving dignified, principled and generous authority'],
                5 => [-1, 'in Virgo, where expansive wisdom is constrained by criticism and detail'],
                6 => [0, 'in Libra, giving balanced judgement and a real instinct for fairness'],
                7 => [1, 'in Scorpio, producing profound, occult and research-oriented wisdom'],
                8 => [2, 'in its own sign Sagittarius, fully expressed. Philosophy, teaching, ethics and higher learning are genuine strengths'],
                9 => [-2, 'debilitated in Capricorn, where faith is replaced by pragmatism. Generosity is calculated and optimism scarce'],
                10 => [0, 'in Aquarius, giving humanitarian and reformist wisdom that sits loose to tradition'],
                11 => [2, 'in its own sign Pisces, deeply expressed. Compassion, spirituality and intuitive understanding are natural'],
            ],
            'Venus' => [
                0 => [-1, 'in Aries, giving impulsive, pursuing affection that cools as quickly as it ignites'],
                1 => [2, 'in its own sign Taurus, fully at ease. Sensual appreciation, artistic taste and lasting attachment'],
                2 => [0, 'in Gemini, where affection is expressed through conversation and wit, and variety matters more than depth'],
                3 => [1, 'in Cancer, giving nurturing, emotionally attached love centred on home and family'],
                4 => [1, 'in Leo, producing warm, loyal, demonstrative affection that requires admiration in return'],
                5 => [-2, 'debilitated in Virgo, where love is analysed and criticised rather than enjoyed. The instinct is to improve the beloved rather than accept them'],
                6 => [2, 'in its own sign Libra, fully expressed. Refinement, diplomacy, artistic sense and genuine partnership skill'],
                7 => [-1, 'in Scorpio, giving intense, possessive and transformative attachment. Jealousy is the risk to manage'],
                8 => [0, 'in Sagittarius, requiring freedom within affection. Love is honest, generous and resistant to confinement'],
                9 => [-1, 'in Capricorn, where affection is dutiful and expressed through provision rather than warmth'],
                10 => [0, 'in Aquarius, giving unconventional, friendship-based love that resists ordinary expectation'],
                11 => [2, 'exalted in Pisces, the strongest position available. Devotional, compassionate, self-giving love and considerable artistic gift'],
            ],
            'Saturn' => [
                0 => [-2, 'debilitated in Aries, where patience and impulse are at war. Effort is begun forcefully and sustained badly, producing repeated frustration'],
                1 => [0, 'in Taurus, giving patient, materially focused endurance. Slow accumulation that does not reverse'],
                2 => [0, 'in Gemini, producing disciplined, structured thinking and careful, sparing speech'],
                3 => [-1, 'in Cancer, where emotional expression is restricted. Feelings are held in and warmth is difficult to show'],
                4 => [-1, 'in Leo, where pride and limitation conflict. Recognition is wanted and withheld, often for years'],
                5 => [1, 'in Virgo, giving meticulous, tireless, detail-perfect work capacity'],
                6 => [2, 'exalted in Libra, the strongest position available. Justice, fairness and balanced judgement, with real staying power in relationships'],
                7 => [0, 'in Scorpio, giving relentless, secretive endurance and the capacity to absorb great pressure'],
                8 => [0, 'in Sagittarius, producing disciplined philosophy and ethics held as obligation rather than inspiration'],
                9 => [2, 'in its own sign Capricorn, fully expressed. Ambition, structure, patience and the ability to build institutions that outlast you'],
                10 => [2, 'in its own sign Aquarius, fully expressed. Systematic, reformist, humanitarian discipline'],
                11 => [-1, 'in Pisces, where structure dissolves. Boundaries are unclear and discipline is hard to hold'],
            ],
            'Rahu' => [
                0 => [0, 'in Aries, giving reckless ambition and a hunger for primacy pursued without much caution'],
                1 => [1, 'in Taurus, where it is comfortable. Strong material desire and a real capacity to accumulate'],
                2 => [2, 'in Gemini, one of its better placements. Exceptional cleverness, communication skill and adaptability, with a tendency to manipulate'],
                3 => [-1, 'in Cancer, producing emotional turbulence and insecurity that drives an unending search for belonging'],
                4 => [0, 'in Leo, giving a powerful hunger for recognition and status, and difficulty tolerating obscurity'],
                5 => [1, 'in Virgo, where obsessive analytical focus becomes genuine technical mastery'],
                6 => [1, 'in Libra, producing unconventional relationships and considerable social and diplomatic cunning'],
                7 => [-1, 'in Scorpio, intensifying obsession, secrecy and the pull toward hidden knowledge'],
                8 => [0, 'in Sagittarius, giving unorthodox belief and a hunger for foreign experience'],
                9 => [1, 'in Capricorn, where ambition is disciplined and turned into sustained worldly achievement'],
                10 => [2, 'in Aquarius, one of its strongest placements. Innovation, technology and the ability to work far ahead of consensus'],
                11 => [-1, 'in Pisces, producing confusion, escapism and susceptibility to illusion and deception'],
            ],
            'Ketu' => [
                0 => [0, 'in Aries, giving detached courage and indifference to the outcome of a fight already entered'],
                1 => [-1, 'in Taurus, producing dissatisfaction with material comfort even when it is abundant'],
                2 => [0, 'in Gemini, giving intuitive understanding that bypasses explanation. You know without being able to say how'],
                3 => [-1, 'in Cancer, bringing emotional detachment and a sense of separation from family or mother'],
                4 => [0, 'in Leo, producing indifference to recognition and discomfort with being the centre of attention'],
                5 => [1, 'in Virgo, giving effortless analytical skill that is exercised without pride or attachment'],
                6 => [0, 'in Libra, bringing detachment within relationship. You are present but not fully held'],
                7 => [2, 'in Scorpio, a strong placement for it. Profound occult insight and genuine spiritual penetration'],
                8 => [2, 'in Sagittarius, giving natural detachment from dogma and a directly intuitive grasp of principle'],
                9 => [0, 'in Capricorn, producing indifference to status and a quiet refusal to compete for it'],
                10 => [1, 'in Aquarius, giving detached, impersonal humanitarian concern'],
                11 => [2, 'in Pisces, the most liberating placement available to it. Strong inclination toward renunciation and genuine mystical capacity'],
            ],
        ];
    }
}
