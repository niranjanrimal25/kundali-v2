<?php

namespace Database\Seeders\Rules;

/**
 * Drishti x Bhava — what a specific graha's gaze does to a specific house.
 * 9 grahas x 12 houses = 108 fragments.
 *
 * AspectRules says what Shani's drishti does in general. This says what
 * it does to YOUR 4th house as opposed to your 10th, which is the
 * difference between a reading and a template.
 *
 * Higher weight than the generic AspectRules, so these win where written.
 * Written to follow "Here the drishti of Shani ...".
 */
class AspectHouseRules
{
    public static function all(): array
    {
        $rows = [];

        foreach (self::map() as $planet => $houses) {
            foreach ($houses as $house => [$polarity, $text]) {
                $rows[] = [
                    'condition_type' => 'aspect_house',
                    'condition_key' => "{$planet}:{$house}",
                    'section' => "house_{$house}",
                    'polarity' => $polarity,
                    'weight' => 72,
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
                1 => [1, 'lends authority and visible presence to the personality, though it also hardens pride'],
                2 => [0, 'brings authority into speech and ties family standing to reputation, while burning through savings'],
                3 => [1, 'strengthens courage and initiative, making self-assertion come more readily'],
                4 => [-1, 'disturbs domestic peace, bringing ego into the home and some distance from the mother'],
                5 => [1, 'strengthens intelligence and confers recognition through children or creative work'],
                6 => [1, 'gives the strength to defeat enemies outright, though it inflames the constitution'],
                7 => [-1, 'brings ego into partnership. Dominance becomes an issue in marriage, and equality has to be worked at'],
                8 => [0, 'exposes hidden matters to daylight, which is uncomfortable but ultimately protective'],
                9 => [2, 'strengthens fortune, principle and the standing of the father'],
                10 => [2, 'confers authority, visibility and real professional advancement'],
                11 => [2, 'brings gains through authority and the support of influential people'],
                12 => [-1, 'drains vitality into expenditure and isolation, and reputation suffers where you cannot be seen'],
            ],
            'Moon' => [
                1 => [1, 'softens the personality and makes the emotional weather visible to everyone who meets you'],
                2 => [1, 'makes income fluctuate and lends warmth and persuasive feeling to speech'],
                3 => [0, 'makes courage depend on mood, and brings emotional closeness with siblings'],
                4 => [2, 'strengthens domestic happiness and the bond with the mother considerably'],
                5 => [1, 'deepens emotional attachment to children and feeds imaginative, creative work'],
                6 => [-1, 'makes health track emotional state, so stress converts into physical complaint'],
                7 => [1, 'brings emotional depth to marriage, alongside a tendency to depend on the partner for stability'],
                8 => [-1, 'makes the mind vulnerable to the chart\'s upheavals. Anxiety and disturbed sleep during difficult periods'],
                9 => [1, 'gives an intuitive rather than doctrinal faith, and a warm bond with the father or teachers'],
                10 => [1, 'makes public standing fluctuate, and suits work involving the public or caregiving'],
                11 => [1, 'brings gains through emotional connection and a socially warm, well-liked circle'],
                12 => [0, 'deepens the inner life and dream world, and inclines toward residence near water or abroad'],
            ],
            'Mars' => [
                1 => [0, 'adds force and physical vigour to the personality, along with a temper that shows'],
                2 => [-1, 'sharpens speech to the point of harm and brings friction over family money'],
                3 => [2, 'strongly reinforces courage and initiative. Obstacles are met head-on and usually cleared'],
                4 => [-2, 'brings conflict into the home and disputes over property. Domestic peace is genuinely hard to keep'],
                5 => [0, 'energises creativity and gives competitive children, with impulsive speculation a hazard'],
                6 => [2, 'is strongly favourable here. Enemies, debts and disease are all defeated decisively'],
                7 => [-2, 'brings friction and quarrel into marriage. This is the classic Mangal Dosha aspect, and it demands a partner who can absorb heat without returning it'],
                8 => [-1, 'raises the risk of accident and surgery, while granting real courage in crisis'],
                9 => [0, 'makes belief militant. Principles are defended aggressively, and friction with the father is likely'],
                10 => [2, 'drives career forward decisively and suits competitive, technical or physical professions'],
                11 => [1, 'brings gains through effort and competition, with friction among peers as the cost'],
                12 => [-1, 'dissipates energy into hidden conflict and expenditure, and disturbs sleep'],
            ],
            'Mercury' => [
                1 => [1, 'sharpens the intellect and makes articulacy a visible part of how you come across'],
                2 => [2, 'strengthens earning through speech, trade and analysis, and makes speech precise'],
                3 => [2, 'strongly reinforces communication, negotiation and useful short travel'],
                4 => [1, 'brings study into the home and favours dealings in property and documents'],
                5 => [2, 'strengthens intelligence and education, and gives bright, communicative children'],
                6 => [0, 'sharpens analytical skill in conflict and litigation, at the cost of persistent anxiety'],
                7 => [1, 'favours contracts, negotiation and a partner who is intellectually engaging'],
                8 => [0, 'gives genuine research and investigative capability, and a mind drawn to hidden things'],
                9 => [2, 'strengthens higher learning, teaching and publishing'],
                10 => [2, 'supports a career built on communication, analysis or commerce'],
                11 => [2, 'brings gains through networks, trade and intellectual work'],
                12 => [0, 'turns the mind inward and favours research conducted in seclusion or abroad'],
            ],
            'Jupiter' => [
                1 => [2, 'protects the constitution and lends dignity, optimism and good judgement to the personality'],
                2 => [2, 'strongly supports wealth and family, and gives speech real moral weight'],
                3 => [1, 'lends purpose to effort and gives beneficial relations with siblings'],
                4 => [2, 'strongly protects home, property and the mother, and gives genuine inner contentment'],
                5 => [2, 'is the classic blessing for children, education and creative intelligence'],
                6 => [1, 'protects health and resolves disputes in your favour, though it can enlarge debts'],
                7 => [2, 'is the single most protective aspect a marriage can receive. It secures a principled partner and repairs damage from elsewhere'],
                8 => [1, 'eases the difficulties of the 8th, supporting longevity and favouring inheritance'],
                9 => [2, 'strongly reinforces fortune, faith, teachers and the father'],
                10 => [2, 'supports career with ethical authority and brings advancement through respect rather than force'],
                11 => [2, 'strongly increases gains and brings genuinely influential well-wishers'],
                12 => [1, 'makes expenditure purposeful and supports spiritual practice and foreign residence'],
            ],
            'Venus' => [
                1 => [2, 'lends attractiveness, charm and social ease to the personality'],
                2 => [2, 'supports wealth and family harmony, and gives a pleasing voice'],
                3 => [1, 'lends artistry to communication and makes travel enjoyable'],
                4 => [2, 'strongly supports domestic comfort, property, vehicles and the mother'],
                5 => [2, 'favours romance, artistic children and genuine creative pleasure'],
                6 => [0, 'softens conflict and eases recovery from illness, though it can indulge bad habits'],
                7 => [2, 'strongly supports marriage, bringing an attractive, refined and cooperative partner'],
                8 => [0, 'eases the harshness of the 8th and can bring gain through marriage or inheritance'],
                9 => [2, 'brings fortunate travel and a refined, aesthetic approach to belief'],
                10 => [2, 'supports a career in design, diplomacy, luxury or the arts, and improves public image'],
                11 => [2, 'strongly increases gains, particularly through partnership and artistic work'],
                12 => [1, 'makes expenditure pleasurable and favours comfort abroad and private relationships'],
            ],
            'Saturn' => [
                1 => [-1, 'weighs on the constitution and lends seriousness and early responsibility to the personality'],
                2 => [-1, 'constrains family wealth and makes speech sparing, guarded and slow'],
                3 => [1, 'is favourable here. It converts courage into sustained, disciplined effort that finishes what it starts'],
                4 => [-2, 'is a heavy aspect. Domestic happiness is delayed, property carries burdens, and inner peace is genuinely hard to hold'],
                5 => [-1, 'delays children and makes education laborious, though what is learned is retained permanently'],
                6 => [2, 'is strongly favourable here. Enemies and chronic difficulties are outlasted rather than defeated, which works just as well'],
                7 => [-1, 'delays marriage and makes it dutiful. The bond is tested early and becomes durable if it survives'],
                8 => [0, 'supports longevity and endurance, while prolonging whatever difficulty the 8th brings'],
                9 => [-1, 'makes faith austere and duty-bound, and distances the father'],
                10 => [1, 'makes career slow, heavy and permanent. Advancement is deserved rather than lucky'],
                11 => [2, 'is favourable here, bringing steady gains through long effort and patient investment'],
                12 => [-1, 'deepens isolation and prolongs expenditure, though it supports serious spiritual discipline'],
            ],
            'Rahu' => [
                1 => [-1, 'distorts self-image, inflating ambition and making you read differently to others than you intend'],
                2 => [-1, 'makes wealth erratic and speech exaggerated or manipulative'],
                3 => [2, 'is favourable here, giving bold, unconventional initiative and foreign connections'],
                4 => [-1, 'unsettles the home and creates dissatisfaction with where you are. Often indicates relocation'],
                5 => [-1, 'distorts judgement in speculation and brings unconventional concerns regarding children'],
                6 => [2, 'is favourable here. Enemies are outmanoeuvred rather than confronted, and legal matters resolve your way'],
                7 => [-2, 'destabilises marriage. It brings an unconventional partner and a gap between expectation and reality that turns into disillusionment unless named early'],
                8 => [-2, 'amplifies upheaval and hidden loss, and pulls the mind toward occult preoccupation'],
                9 => [-1, 'unsettles inherited belief and creates friction with the father and with tradition'],
                10 => [1, 'drives unconventional career rise, rapid but requiring care over the means used'],
                11 => [2, 'is strongly favourable here, bringing large gains and powerful, unorthodox networks'],
                12 => [-1, 'increases hidden expenditure and psychological disturbance, and pulls toward foreign residence'],
            ],
            'Ketu' => [
                1 => [-1, 'detaches you from your own self-image, producing a recurring sense of not quite belonging'],
                2 => [-1, 'loosens attachment to wealth and family, and makes speech sparse'],
                3 => [1, 'gives sudden, unattached initiative and unusual technical skill'],
                4 => [-1, 'brings emotional distance from home and mother, and unsettles the sense of a fixed place'],
                5 => [-1, 'creates distance regarding children and blocks creative confidence, while deepening intuition'],
                6 => [2, 'is favourable here. Enemies and chronic illness tend to dissolve rather than be fought'],
                7 => [-1, 'introduces emotional distance into marriage. The partnership persists but feels incomplete'],
                8 => [1, 'strengthens occult and research capacity and eases fear of the transformations the 8th brings'],
                9 => [1, 'detaches belief from dogma, giving direct intuitive understanding and a pull toward pilgrimage'],
                10 => [-1, 'withdraws interest from career and status, so professional direction can quietly dissolve'],
                11 => [0, 'makes gains arrive unsought and matter less than expected, and thins the social circle'],
                12 => [2, 'is strongly favourable here, supporting genuine renunciation and spiritual release'],
            ],
        ];
    }
}
