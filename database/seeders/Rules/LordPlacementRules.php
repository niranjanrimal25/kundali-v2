<?php

namespace Database\Seeders\Rules;

/**
 * Bhavesha placement — the lord of each house, placed in each house.
 * 12 x 12 = 144 fragments.
 *
 * This is the layer that separates a real reading from a generic one.
 * "Mars in the 8th" is a placement; "the lord of your ascendant in the
 * 8th" is a causal statement about your life.
 *
 * Fragments are written to follow the phrase "Its lord <Graha> is ..."
 */
class LordPlacementRules
{
    public static function all(): array
    {
        $rows = [];

        foreach (self::map() as $ownHouse => $placements) {
            foreach ($placements as $inHouse => [$polarity, $text]) {
                $rows[] = [
                    'condition_type' => 'lord_in_house',
                    'condition_key' => "{$ownHouse}:{$inHouse}",
                    'section' => "house_{$ownHouse}",
                    'polarity' => $polarity,
                    'weight' => 85,
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
            // ---- 1st lord: the self, directed into each area of life ----
            1 => [
                1 => [2, 'placed in the Lagna itself, which strengthens the constitution and makes self-direction the governing theme of life. You are the principal author of your own circumstances, for better and worse'],
                2 => [1, 'placed in the 2nd, tying identity to wealth, family and speech. Earning capacity is good, and how you speak substantially determines how you are received'],
                3 => [1, 'placed in the 3rd, giving self-made courage and advancement through personal effort rather than inheritance. Siblings and short journeys figure prominently'],
                4 => [1, 'placed in the 4th, anchoring identity in home, mother and inner peace. Property is acquired, and domestic disturbance affects you more than most'],
                5 => [2, 'placed in the 5th, favouring intelligence, children and creative expression. Learning comes readily and past merit supports the present life'],
                6 => [-1, 'placed in the 6th, which turns the self toward conflict, service and health. You overcome enemies but expend yourself doing so, and must guard against chronic depletion'],
                7 => [0, 'placed in the 7th, making partnership central to self-definition. You discover yourself through others, which is a strength in collaboration and a liability in isolation'],
                8 => [-2, 'placed in the 8th, one of the more difficult placements for the Lagna lord. It brings health vulnerability, sudden reversals and a life marked by transformation, alongside genuine research capacity. Haste and risk-taking are the specific dangers to manage'],
                9 => [2, 'placed in the 9th, among the most fortunate placements available. Fortune supports you, a father or teacher figure aids materially, and ethical clarity guides decisions'],
                10 => [2, 'placed in the 10th, directing the self powerfully toward career and public standing. Professional identity and personal identity are nearly the same thing for you'],
                11 => [2, 'placed in the 11th, favouring gains, networks and the fulfilment of ambition. Friends and elder siblings assist, and income grows steadily'],
                12 => [-1, 'placed in the 12th, inclining toward foreign residence, solitude and inner life. Expenditure runs high and worldly ambition is muted, but spiritual capacity is genuine'],
            ],

            // ---- 2nd lord: wealth, family, speech ----
            2 => [
                1 => [1, 'placed in the Lagna, making you personally responsible for building wealth. Earning is self-driven, and money is tied closely to your own effort and reputation'],
                2 => [2, 'placed in the 2nd itself, strengthening wealth, family stability and the authority of your speech'],
                3 => [1, 'placed in the 3rd, indicating income through communication, initiative and siblings. Wealth grows by repeated small effort rather than windfall'],
                4 => [1, 'placed in the 4th, linking wealth to property, vehicles and the mother. Assets are held rather than circulated'],
                5 => [2, 'placed in the 5th, favouring income through intelligence, speculation or creative work, and wealth that benefits children'],
                6 => [-1, 'placed in the 6th, exposing wealth to debt, litigation and erosion. Money is earned through service and conflict, and financial discipline is essential'],
                7 => [1, 'placed in the 7th, connecting wealth to marriage and partnership. The spouse contributes materially, and business partnerships are favoured'],
                8 => [-2, 'placed in the 8th, which destabilises finances and brings sudden losses alongside possible inheritance. Income is irregular and should not be assumed to continue'],
                9 => [2, 'placed in the 9th, a fortunate combination bringing wealth through ethical means, the father, or long-distance connections'],
                10 => [2, 'placed in the 10th, tying income directly to career and professional standing. Earnings rise as reputation rises'],
                11 => [2, 'placed in the 11th, a strong wealth combination. Income accumulates steadily and ambitions are financially fulfilled'],
                12 => [-2, 'placed in the 12th, indicating heavy expenditure, loss and money that drains toward foreign matters or institutions. Saving requires deliberate structure'],
            ],

            // ---- 3rd lord: courage, siblings, effort ----
            3 => [
                1 => [1, 'placed in the Lagna, making courage and self-assertion personal traits rather than acquired skills. You advance by your own initiative'],
                2 => [1, 'placed in the 2nd, converting effort directly into wealth. Communication and persistence build the family fortune'],
                3 => [2, 'placed in the 3rd itself, granting strong courage, capable siblings and success through sustained personal effort'],
                4 => [0, 'placed in the 4th, directing effort toward home and property, though it can unsettle domestic peace with restlessness'],
                5 => [1, 'placed in the 5th, giving creative courage and intellectual initiative. Siblings support education and children'],
                6 => [0, 'placed in the 6th, indicating courage expressed through conflict and competition. Disputes with siblings are possible but you prevail in them'],
                7 => [1, 'placed in the 7th, bringing initiative into partnership and possible business collaboration with siblings'],
                8 => [-1, 'placed in the 8th, where courage meets sudden obstruction. Siblings may face difficulty, and bold ventures carry hidden risk'],
                9 => [1, 'placed in the 9th, indicating fortune through effort, travel and the support of siblings in matters of belief or higher learning'],
                10 => [2, 'placed in the 10th, converting personal initiative into professional advancement. Self-promotion is a genuine career asset'],
                11 => [2, 'placed in the 11th, producing gains through effort and strong, beneficial relations with siblings and peers'],
                12 => [-1, 'placed in the 12th, dissipating effort into unproductive channels. Siblings may be distant, and initiative is expended abroad or in seclusion'],
            ],

            // ---- 4th lord: home, mother, inner peace ----
            4 => [
                1 => [1, 'placed in the Lagna, making home and emotional security central to identity. You carry the atmosphere of your upbringing visibly'],
                2 => [1, 'placed in the 2nd, linking property to family wealth. Assets are inherited or held within the family'],
                3 => [0, 'placed in the 3rd, indicating relocation, restlessness at home, and property matters involving siblings'],
                4 => [2, 'placed in the 4th itself, granting domestic happiness, property, a supportive mother and genuine inner contentment'],
                5 => [1, 'placed in the 5th, bringing happiness through children and education, with a home that is a place of learning'],
                6 => [-2, 'placed in the 6th, introducing conflict, debt or litigation into home and property. The relationship with the mother carries strain'],
                7 => [1, 'placed in the 7th, connecting home to marriage. The partner shapes domestic life substantially'],
                8 => [-2, 'placed in the 8th, bringing loss of property, disturbance in the home and health concerns for the mother. Domestic peace is repeatedly interrupted'],
                9 => [2, 'placed in the 9th, a fortunate placement linking home to fortune, faith and the father. Property is gained through good fortune'],
                10 => [1, 'placed in the 10th, indicating work conducted from home or a career involving property, education or vehicles'],
                11 => [2, 'placed in the 11th, producing gains through property and a home that expands your social circle'],
                12 => [-2, 'placed in the 12th, indicating loss of property, residence abroad and separation from the mother or homeland'],
            ],

            // ---- 5th lord: intelligence, children, merit ----
            5 => [
                1 => [2, 'placed in the Lagna, making intelligence and creativity defining personal traits. Past merit supports you directly'],
                2 => [1, 'placed in the 2nd, converting intelligence into wealth and bringing children who strengthen the family'],
                3 => [1, 'placed in the 3rd, giving creative communication and intellectual courage'],
                4 => [1, 'placed in the 4th, indicating education pursued at home and happiness derived from children'],
                5 => [2, 'placed in the 5th itself, strongly favouring intelligence, children, creativity and accumulated merit'],
                6 => [-2, 'placed in the 6th, bringing difficulty or delay regarding children, and intelligence expended on conflict and service'],
                7 => [1, 'placed in the 7th, indicating a love marriage and a partner who shares intellectual or creative interests'],
                8 => [-2, 'placed in the 8th, causing anxiety and obstruction regarding children, though it grants deep research capability and occult intelligence'],
                9 => [2, 'placed in the 9th, an excellent combination joining intelligence with fortune. Higher learning and ethical wisdom flourish'],
                10 => [2, 'placed in the 10th, a strong Raja Yoga tendency. Intelligence and creativity translate directly into professional standing'],
                11 => [2, 'placed in the 11th, producing gain through intelligence, speculation and children'],
                12 => [-1, 'placed in the 12th, turning intelligence inward toward spirituality and research. Children may live at a distance'],
            ],

            // ---- 6th lord: enemies, debt, disease (dusthana lord) ----
            6 => [
                1 => [-2, 'placed in the Lagna, bringing health vulnerability and a tendency to create your own obstacles. You are frequently your own principal adversary'],
                2 => [-1, 'placed in the 2nd, exposing family and wealth to debt and dispute. Speech may become argumentative'],
                3 => [1, 'placed in the 3rd, a favourable Vipareeta tendency. Enemies are defeated through personal courage, though sibling friction persists'],
                4 => [-1, 'placed in the 4th, bringing disturbance to the home and possible property disputes'],
                5 => [-1, 'placed in the 5th, indicating concern regarding children and losses through speculation'],
                6 => [2, 'placed in the 6th itself, a strong Vipareeta Raja Yoga configuration. Enemies, debts and disease are neutralised by their own excess'],
                7 => [-2, 'placed in the 7th, introducing conflict, litigation or health difficulty into the marriage. Disputes with the partner require deliberate management'],
                8 => [1, 'placed in the 8th, a Vipareeta combination. Difficulties cancel each other, and you may gain unexpectedly from circumstances that damage others'],
                9 => [-1, 'placed in the 9th, creating friction with the father, teachers or established belief'],
                10 => [0, 'placed in the 10th, indicating a career in service, health, law or dispute resolution. Competition is constant but survivable'],
                11 => [1, 'placed in the 11th, producing gain through competitive fields, litigation or service industries'],
                12 => [1, 'placed in the 12th, a Vipareeta combination in which enemies and debts dissolve of their own accord'],
            ],

            // ---- 7th lord: spouse, partnership ----
            7 => [
                1 => [1, 'placed in the Lagna, bringing the partner close into your own sphere. You may marry someone already known to you, and the relationship shapes your identity directly'],
                2 => [1, 'placed in the 2nd, linking marriage to family wealth and domestic stability. The union tends to improve financial standing'],
                3 => [0, 'placed in the 3rd, suggesting a partner encountered through siblings, neighbours or short travel, and a relationship requiring continual communication'],
                4 => [1, 'placed in the 4th, bringing the marriage into the home and often the partner into the family property'],
                5 => [1, 'placed in the 5th, indicating a love match and a relationship in which children figure centrally'],
                6 => [-2, 'placed in the 6th, which introduces friction, litigation or health concerns into the marriage. Disputes are likely and require deliberate management rather than avoidance'],
                7 => [2, 'placed in the 7th itself, strengthening the marriage considerably. The partner is well-matched and the union stable'],
                8 => [-2, 'placed in the 8th, bringing obstacles, secrecy or sudden disturbance to married life. This placement demands transparency; concealment magnifies every difficulty it brings'],
                9 => [2, 'placed in the 9th, indicating a fortunate marriage that expands your world. The partner may come from a distance or a different background and raises your circumstances'],
                10 => [1, 'placed in the 10th, connecting marriage to career and public life. The partner assists professionally, or you meet through work'],
                11 => [1, 'placed in the 11th, bringing gain through marriage and a partner who widens your social network'],
                12 => [-1, 'placed in the 12th, indicating separation, foreign residence or extended periods apart. Distance is a recurring feature of the relationship'],
            ],

            // ---- 8th lord: longevity, upheaval (dusthana lord) ----
            8 => [
                1 => [-2, 'placed in the Lagna, affecting vitality and bringing a life punctuated by sudden change. Health requires attention from early on'],
                2 => [-1, 'placed in the 2nd, destabilising finances and family, though inheritance is possible'],
                3 => [1, 'placed in the 3rd, a Vipareeta tendency giving courage in crisis and survival of difficulty'],
                4 => [-2, 'placed in the 4th, disturbing home and inner peace, with concerns regarding the mother or property'],
                5 => [-1, 'placed in the 5th, bringing anxiety over children and losses through speculation, alongside genuine occult aptitude'],
                6 => [1, 'placed in the 6th, a Vipareeta Raja Yoga configuration in which crisis defeats crisis and you emerge intact'],
                7 => [-2, 'placed in the 7th, affecting the partner\'s wellbeing and bringing hidden strain into the marriage'],
                8 => [2, 'placed in the 8th itself, granting long life, resilience and genuine capacity for research into hidden matters'],
                9 => [-1, 'placed in the 9th, disturbing fortune and the relationship with the father or with inherited belief'],
                10 => [-1, 'placed in the 10th, bringing professional instability and sudden changes of direction, alongside aptitude for investigative work'],
                11 => [1, 'placed in the 11th, producing unexpected gains, inheritance or income from non-obvious sources'],
                12 => [1, 'placed in the 12th, a Vipareeta combination favouring spiritual liberation and a peaceful end'],
            ],

            // ---- 9th lord: fortune, dharma, father ----
            9 => [
                1 => [2, 'placed in the Lagna, making you personally fortunate. Luck attaches to your presence and ethical instinct guides you'],
                2 => [2, 'placed in the 2nd, bringing wealth through fortune, the father, or ethical enterprise'],
                3 => [1, 'placed in the 3rd, indicating fortune through effort, travel and communication'],
                4 => [2, 'placed in the 4th, granting happiness, property and a fortunate domestic life'],
                5 => [2, 'placed in the 5th, an excellent combination uniting fortune with intelligence and children'],
                6 => [-2, 'placed in the 6th, obstructing fortune and creating conflict with the father or with teachers. Luck must be worked for and is rarely given'],
                7 => [1, 'placed in the 7th, bringing a fortunate marriage and a partner who improves your circumstances'],
                8 => [-2, 'placed in the 8th, disrupting fortune and bringing difficulty concerning the father. Belief is tested severely before it becomes genuine'],
                9 => [2, 'placed in the 9th itself, one of the strongest indications of good fortune, ethical clarity and a supportive father or guru'],
                10 => [2, 'placed in the 10th, a powerful Raja Yoga combination joining fortune to career. Professional rise is substantially supported'],
                11 => [2, 'placed in the 11th, producing large gains through fortune, faith and influential well-wishers'],
                12 => [-1, 'placed in the 12th, directing fortune toward foreign lands, charity and spiritual pursuit rather than worldly gain'],
            ],

            // ---- 10th lord: career, status ----
            10 => [
                1 => [2, 'placed in the Lagna, fusing career with personal identity. You build your profession on your own name and presence'],
                2 => [1, 'placed in the 2nd, directing career toward wealth accumulation, family enterprise or work involving speech and finance'],
                3 => [1, 'placed in the 3rd, favouring careers built on communication, initiative and self-marketing'],
                4 => [1, 'placed in the 4th, suiting work from home, in property, education or vehicles. The mother or homeland figures in professional life'],
                5 => [2, 'placed in the 5th, favouring creative, speculative or education-related careers. Intelligence is your professional instrument'],
                6 => [0, 'placed in the 6th, indicating a career in service, health, law or dispute resolution. Competition is constant but you are equipped for it'],
                7 => [1, 'placed in the 7th, favouring business, partnership and public dealing. You work better with someone than alone'],
                8 => [-1, 'placed in the 8th, bringing professional instability and sudden change of course, alongside genuine aptitude for research, insurance or investigative work'],
                9 => [2, 'placed in the 9th, a strong Raja Yoga combination. Career is supported by fortune, teachers and ethical standing, and rises substantially'],
                10 => [2, 'placed in the 10th itself, giving a powerful and self-sustaining professional life with real authority'],
                11 => [2, 'placed in the 11th, converting career directly into income and gain. Professional networks are your principal asset'],
                12 => [-1, 'placed in the 12th, indicating career abroad, in institutions, or in work removed from public visibility. Recognition is limited but the work may matter more than the recognition'],
            ],

            // ---- 11th lord: gains, networks ----
            11 => [
                1 => [2, 'placed in the Lagna, making you personally effective at generating income. Ambition is self-driven and generally fulfilled'],
                2 => [2, 'placed in the 2nd, a strong wealth combination in which gains accumulate into lasting family assets'],
                3 => [1, 'placed in the 3rd, indicating income through communication, effort and siblings'],
                4 => [1, 'placed in the 4th, producing gains through property, vehicles and domestic enterprise'],
                5 => [2, 'placed in the 5th, giving income through intelligence, creativity and speculation'],
                6 => [0, 'placed in the 6th, indicating income from competitive or service fields, with friends who may also become rivals'],
                7 => [1, 'placed in the 7th, bringing gains through partnership, marriage and public dealing'],
                8 => [-1, 'placed in the 8th, making income irregular and dependent on circumstances outside your control, though inheritance is possible'],
                9 => [2, 'placed in the 9th, producing fortunate and ethically sound gains, often through travel or influential mentors'],
                10 => [2, 'placed in the 10th, tying income directly to professional success. Career growth and income growth move together'],
                11 => [2, 'placed in the 11th itself, strongly favouring income, networks and the fulfilment of long-held ambitions'],
                12 => [-1, 'placed in the 12th, indicating that gains drain away into expenditure, foreign matters or charity as fast as they arrive'],
            ],

            // ---- 12th lord: loss, expenditure, moksha ----
            12 => [
                1 => [-1, 'placed in the Lagna, inclining toward withdrawal, high expenditure and a temperament more inward than worldly'],
                2 => [-1, 'placed in the 2nd, draining family wealth and indicating expenditure that outpaces income'],
                3 => [0, 'placed in the 3rd, dissipating effort, though it favours travel and work conducted at a distance'],
                4 => [-1, 'placed in the 4th, indicating separation from home or mother, and property held far from your birthplace'],
                5 => [-1, 'placed in the 5th, bringing expenditure on children and education, with speculative losses possible'],
                6 => [1, 'placed in the 6th, a Vipareeta combination in which losses cancel debts and enemies disperse of their own accord'],
                7 => [-1, 'placed in the 7th, indicating a partner from a distance, or periods of separation within the marriage'],
                8 => [1, 'placed in the 8th, a Vipareeta combination favouring a peaceful end and freedom from accumulated obligation'],
                9 => [-1, 'placed in the 9th, indicating expenditure on religion, travel or the father, and belief that leads away from convention'],
                10 => [-1, 'placed in the 10th, indicating a career abroad or in institutional seclusion, with limited public recognition'],
                11 => [0, 'placed in the 11th, where income and expenditure move together. Money arrives and departs in equal measure'],
                12 => [2, 'placed in the 12th itself, strengthening the capacity for renunciation, foreign success and genuine spiritual liberation'],
            ],
        ];
    }
}
