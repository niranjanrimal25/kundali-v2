<?php

namespace Database\Seeders\Rules;

/**
 * Vimshottari Dasha — what a period lord delivers from the house it sits in.
 * 9 grahas x 12 houses = 108 fragments.
 *
 * This is the timing layer, and the part of a reading people actually act
 * on. A dasha introduces nothing new; it activates what the birth chart
 * already holds. So the fragment is keyed on where the period lord SITS,
 * not merely on which graha it is.
 *
 * Written to follow "During this period, ..." in the dasha section.
 */
class DashaRules
{
    public static function all(): array
    {
        $rows = [];

        foreach (self::map() as $planet => $houses) {
            foreach ($houses as $house => [$polarity, $text]) {
                $rows[] = [
                    'condition_type' => 'dasha_lord_house',
                    'condition_key' => "{$planet}:{$house}",
                    'section' => 'dasha',
                    'polarity' => $polarity,
                    'weight' => 82,
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
                1 => [1, 'matters of health, identity and personal standing come to the front. Confidence rises, and you are more visible than usual'],
                2 => [1, 'income, family and speech dominate. Earnings improve, though disputes over money or inheritance can surface'],
                3 => [1, 'courage and initiative increase. Favourable for travel, self-promotion and asserting yourself with siblings or colleagues'],
                4 => [0, 'domestic matters, property and the mother occupy attention. Comfort may be gained, but domestic friction is common'],
                5 => [2, 'intelligence, children and creative work are activated. Favourable for education, recognition and speculative gain'],
                6 => [0, 'conflict, competition and health come to the front. You defeat opposition, but the period is draining and rest is not optional'],
                7 => [0, 'marriage, partnership and public dealing dominate. Relationships are tested, and ego in partnership is the specific risk'],
                8 => [-2, 'a period of upheaval and health vulnerability. Avoid unnecessary risk, and treat sudden opportunities with more scepticism than usual'],
                9 => [2, 'fortune, the father and higher learning are activated. One of the more favourable periods available for advancement and travel'],
                10 => [2, 'career and public standing are strongly activated. Promotion, authority and visible recognition are well indicated'],
                11 => [2, 'gains, income and influential contacts dominate. Ambitions held for years can complete during this period'],
                12 => [-1, 'expenditure rises and energy turns inward. Foreign travel, solitude or institutional work is indicated, with limited public reward'],
            ],
            'Moon' => [
                1 => [1, 'emotional life and personal identity are foregrounded. Mood governs the period more than circumstance does'],
                2 => [1, 'family, wealth and domestic security occupy attention. Income becomes changeable rather than fixed'],
                3 => [0, 'communication, short travel and siblings dominate. Restlessness is the characteristic feeling of the period'],
                4 => [2, 'home, mother and inner peace are strongly activated. Favourable for property, vehicles and domestic settlement'],
                5 => [1, 'children, education and creative work come forward. Emotional fulfilment through what you make or raise'],
                6 => [-1, 'health and conflict dominate, with emotional strain the main cost. Digestive and stress-related complaints are common'],
                7 => [1, 'marriage and partnership are foregrounded. Favourable for union, though emotional dependency is the risk'],
                8 => [-2, 'a psychologically difficult period. Anxiety, disturbed sleep and emotional upheaval. Mental health warrants genuine attention here'],
                9 => [1, 'fortune, travel and belief are activated. A period of emotional expansion and improved circumstances'],
                10 => [1, 'career comes forward, often involving the public, caregiving or property. Professional standing fluctuates'],
                11 => [1, 'gains and social connection increase. Income improves, and friendships formed now matter'],
                12 => [-1, 'withdrawal, foreign residence and inner life dominate. Expenditure rises and isolation is felt keenly'],
            ],
            'Mars' => [
                1 => [0, 'energy and assertion rise sharply. Productive if directed, and self-damaging if not. Injury and inflammation are possible'],
                2 => [0, 'aggressive earning and family dispute both feature. Speech becomes sharper, and it costs you something'],
                3 => [2, 'one of the strongest periods for Mangala. Courage, initiative and competitive success. Favourable for property and self-employment'],
                4 => [-1, 'domestic conflict and property disputes arise. Peace at home is disturbed, and the mother may face difficulty'],
                5 => [0, 'energetic creativity and competitive success, with impulsive speculation a genuine hazard'],
                6 => [2, 'an excellent period for Mangala. Enemies, litigation, debt and disease are all defeated decisively'],
                7 => [-1, 'friction in marriage and partnership. Disputes escalate quickly, and business partnerships strain'],
                8 => [-2, 'a period demanding real caution. Accident, surgery and sudden loss are indicated. Avoid speculation and physical risk'],
                9 => [1, 'energetic pursuit of fortune, travel and principle. Conflict with the father or teachers is possible'],
                10 => [2, 'a strong career period. Decisive professional action, promotion through competence, and authority gained by effort'],
                11 => [2, 'gains through effort and competition. Income rises, though friction with peers or siblings accompanies it'],
                12 => [-2, 'energy dissipates and losses mount. Hidden enemies, hospital visits and expenditure without return'],
            ],
            'Mercury' => [
                1 => [1, 'communication, learning and adaptability come forward. A good period for study, writing and repositioning yourself'],
                2 => [2, 'strongly favourable for earning, particularly through speech, trade, writing or analysis'],
                3 => [2, 'an excellent period for communication, short travel, negotiation and self-marketing'],
                4 => [1, 'property transactions, study at home and matters involving vehicles or documents come forward'],
                5 => [2, 'intelligence and creativity are strongly activated. Excellent for education, examinations and intellectual work'],
                6 => [0, 'analytical work in competitive or service fields. Favourable for litigation and problem-solving; anxiety is the cost'],
                7 => [1, 'contracts, negotiation and partnership dominate. Favourable for business agreements and trade'],
                8 => [-1, 'research, investigation and hidden matters dominate. The period is mentally taxing, and documents and contracts need unusual care'],
                9 => [2, 'higher learning, publishing and travel are activated. Excellent for teaching and advisory work'],
                10 => [2, 'a strong career period built on communication, analysis and intellectual skill'],
                11 => [2, 'income through communication, networks and trade. Gains arrive through people you know'],
                12 => [-1, 'mental restlessness, expenditure on documents or travel, and work conducted at a distance or in seclusion'],
            ],
            'Jupiter' => [
                1 => [2, 'one of the most favourable periods available. Wisdom, health, optimism and general expansion of circumstances'],
                2 => [2, 'strongly favourable for wealth accumulation, family growth and authority in speech'],
                3 => [0, 'effort and communication expand, though Guru is not at its strongest here. Progress is real but unremarkable'],
                4 => [2, 'excellent for property, domestic happiness, education and the mother\'s wellbeing'],
                5 => [2, 'among the best periods in any chart. Children, education, creativity and recognition all flourish'],
                6 => [-1, 'Guru is weakened in the house of conflict. Legal and health matters expand rather than resolve'],
                7 => [2, 'strongly favourable for marriage and partnership. Frequently the period in which marriage occurs'],
                8 => [0, 'a period of deep study, inheritance and transformation. Difficult in texture but genuinely maturing'],
                9 => [2, 'the most fortunate placement for a Guru period. Fortune, faith, travel, teachers and the father all support you'],
                10 => [2, 'a strong career period bringing ethical authority, advancement and public respect'],
                11 => [2, 'large gains, fulfilment of long-held ambitions and support from influential well-wishers'],
                12 => [0, 'expenditure goes to worthy causes, foreign travel and spiritual practice. The period is materially quiet, inwardly productive'],
            ],
            'Venus' => [
                1 => [2, 'comfort, attractiveness and social ease increase. A pleasant and generally fortunate period'],
                2 => [2, 'strongly favourable for wealth, family harmony and pleasures of food, art and comfort'],
                3 => [1, 'creative communication and enjoyable short travel. Favourable for artistic and media work'],
                4 => [2, 'excellent for property, vehicles, domestic comfort and the mother. Home life becomes genuinely pleasant'],
                5 => [2, 'romance, children and creative work flourish. Among the most enjoyable periods available'],
                6 => [-1, 'relationship difficulty arises, with health matters concerning the reproductive or urinary system. Pleasures turn costly'],
                7 => [2, 'marriage and partnership strongly activated. Frequently the period in which marriage occurs'],
                8 => [-1, 'hidden relationships, scandal and unexpected expenditure on pleasure surface. Discretion is worth more than usual'],
                9 => [2, 'fortunate travel, favourable marriage prospects and gain through refined or artistic pursuits'],
                10 => [2, 'career advancement through charm, design, diplomacy or the arts. Public image improves markedly'],
                11 => [2, 'gains through art, luxury, women or partnership. Income and social circle both expand'],
                12 => [0, 'expenditure runs to comfort, pleasure, foreign travel and private relationships. It is enjoyable but not accumulative'],
            ],
            'Saturn' => [
                1 => [-1, 'a demanding period. Health, energy and confidence are reduced, and responsibility increases. What is built now is durable'],
                2 => [-1, 'financial constraint and family responsibility. Savings are tested and speech becomes guarded'],
                3 => [2, 'one of the best periods for Shani. Sustained effort produces real, permanent results. Favourable for property and self-employment'],
                4 => [-2, 'domestic difficulty, property burdens and concern for the mother. Inner peace is genuinely hard to hold during this period'],
                5 => [-1, 'delay or worry regarding children and education. Creativity feels blocked, though discipline deepens'],
                6 => [2, 'an excellent Shani period. Enemies, debts, litigation and chronic illness are defeated by sheer persistence'],
                7 => [-1, 'marriage is delayed, tested or burdened with duty. Existing partnerships turn heavy but rarely break'],
                8 => [-2, 'among the most difficult periods in any chart. Chronic difficulty, loss and prolonged obstruction. Endurance is the only strategy that works'],
                9 => [0, 'fortune is slowed and belief tested. Relations with the father or teachers become dutiful rather than warm'],
                10 => [1, 'a career period of heavy responsibility. Advancement is slow, deserved and permanent once achieved'],
                11 => [2, 'a strong period for gain. Income from sustained effort, elder-brother figures and long-term investment'],
                12 => [-1, 'isolation, expenditure and foreign residence. Spiritually productive, materially barren'],
            ],
            'Rahu' => [
                1 => [0, 'identity shifts markedly. Ambition surges, appearance and reputation change, and others read you differently'],
                2 => [0, 'finances fluctuate sharply. Income can arrive from unorthodox sources, and family friction accompanies it'],
                3 => [2, 'one of the strongest periods for Rahu. Bold initiative, foreign connections and competitive success'],
                4 => [-1, 'domestic upheaval and dissatisfaction at home. Frequently brings relocation, sometimes abroad'],
                5 => [-1, 'speculative risk and concerns regarding children. Unconventional creativity alongside poor judgement'],
                6 => [2, 'an excellent Rahu period. Enemies are outmanoeuvred and competitive and legal situations resolve in your favour'],
                7 => [-1, 'partnership turns unconventional or unstable. Marriage may occur suddenly or across cultural lines, with disillusionment following'],
                8 => [-2, 'sudden upheaval, hidden losses and occult preoccupation mark the time. A period requiring genuine caution in money and health'],
                9 => [0, 'unorthodox belief, foreign travel and friction with tradition or the father feature'],
                10 => [2, 'a powerful career period. Rapid, often unconventional rise. Guard the means as carefully as the ends'],
                11 => [2, 'the strongest Rahu placement for gain. Substantial income, influential networks and fulfilment of large ambitions'],
                12 => [-1, 'foreign residence, hidden expenditure and psychological disturbance arise. Isolation is felt acutely'],
            ],
            'Ketu' => [
                1 => [-1, 'detachment from identity and direction sets in. Confidence wavers and old ambitions stop making sense'],
                2 => [-1, 'financial detachment and family distance grow. Speech becomes sparse and wealth holds less interest'],
                3 => [1, 'sudden decisive initiative and unusual technical skill emerge, exercised without much attachment to the outcome'],
                4 => [-1, 'separation from home or mother is indicated. Domestic peace is elusive and relocation is common'],
                5 => [-1, 'distance from children and blocked creativity appear, alongside genuine spiritual or occult study'],
                6 => [2, 'an excellent Ketu period. Enemies simply disperse, debts clear and chronic illness resolves unexpectedly'],
                7 => [-1, 'emotional distance enters the marriage. The partnership continues but feels unfinished or separate'],
                8 => [0, 'deep occult and research capacity opens, alongside sudden unexplained difficulty. Transformative rather than comfortable'],
                9 => [1, 'genuine spiritual insight and detachment from inherited belief develop. Pilgrimage and solitary travel are indicated'],
                10 => [-1, 'interest in career and public standing falls away. Professional direction changes or quietly dissolves'],
                11 => [0, 'gains arrive without pursuit and matter less than expected. Social circle thins by choice'],
                12 => [2, 'the strongest Ketu placement. Genuine spiritual progress, renunciation and release from long-standing obligation'],
            ],
        ];
    }
}
