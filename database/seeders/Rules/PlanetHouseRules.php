<?php

namespace Database\Seeders\Rules;

/**
 * Graha in Bhava — all 9 grahas across all 12 houses (108 fragments).
 *
 * Provenance: standard Parashari placement logic as taught in modern
 * Vedic practice. Classical in structure; the wording and the
 * contemporary professional/social mappings are a modern synthesis.
 */
class PlanetHouseRules
{
    public static function all(): array
    {
        $rows = [];

        foreach (self::map() as $house => $planets) {
            foreach ($planets as $planet => [$polarity, $text]) {
                $rows[] = [
                    'condition_type' => 'planet_house',
                    'condition_key' => "{$planet}:{$house}",
                    'section' => "house_{$house}",
                    'polarity' => $polarity,
                    'weight' => 70,
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
            1 => [
                'Sun' => [0, 'Surya in the Lagna gives authority in bearing and a strong sense of personal dignity, though it hardens easily into pride and difficulty accepting correction. The father figure looms large in the formation of character'],
                'Moon' => [1, 'Chandra in the Lagna produces emotional responsiveness, an attractive manner and a mind that registers atmosphere quickly. Stability of mood is the lifelong work, and the mother\'s influence on temperament is decisive'],
                'Mars' => [-1, 'Mangal in the Lagna gives physical vigour, competitive drive and a quick temper. It sharpens courage and shortens patience, and inclines toward injury, scars or surgical intervention. Restraint must be learned deliberately, as it is not native'],
                'Mercury' => [1, 'Budha in the Lagna gives quick comprehension, fluent speech and a youthful manner that persists well into later life. Nervous energy and overthinking are the accompanying costs'],
                'Jupiter' => [2, 'Guru in the Lagna is among the most protective placements available. It confers optimism, ethical instinct and a natural gravity that draws trust, and it restrains the harsher tendencies of the rising sign'],
                'Venus' => [2, 'Shukra in the Lagna gives physical attractiveness, refined taste and easy social warmth. Comfort is sought consistently, and there is reluctance to endure hardship that could be avoided'],
                'Saturn' => [-1, 'Shani in the Lagna imposes seriousness early. Childhood tends to carry responsibility or restriction, and the personality forms around endurance rather than ease. Its gift is discipline that outlasts everyone else\'s; its cost is a persistent undertone of insufficiency'],
                'Rahu' => [-1, 'Rahu in the Lagna produces an unusual, magnetic and somewhat unclassifiable presence. Ambition is strong and identity unsettled, with a pull toward the foreign or unconventional. Self-image tends to be either inflated or obscured, rarely accurate'],
                'Ketu' => [-1, 'Ketu in the Lagna gives detachment from ordinary self-definition and an inward, questioning cast of mind. There is genuine aptitude for contemplative or investigative work, alongside a recurring sense of not quite belonging'],
            ],
            2 => [
                'Sun' => [0, 'Surya in the 2nd gives authoritative speech and pride in family standing. Wealth arrives through position rather than trade, and the relationship with the father affects financial confidence'],
                'Moon' => [0, 'Chandra in the 2nd ties money to mood and brings fluctuating finances. Speech is gentle and persuasive, and family warmth matters greatly to your sense of security'],
                'Mars' => [-1, 'Mangal in the 2nd produces blunt, sometimes cutting speech and friction within the family. Money is earned energetically but spent impulsively, and arguments over property are possible'],
                'Mercury' => [1, 'Budha in the 2nd gives articulate, persuasive speech and genuine commercial intelligence. Income through communication, trade or calculation is well indicated'],
                'Jupiter' => [2, 'Guru in the 2nd is strongly favourable for wealth and family. Speech carries authority and truthfulness, and resources tend to accumulate rather than drain'],
                'Venus' => [2, 'Shukra in the 2nd gives a pleasant voice, refined taste and comfortable finances. Money flows toward beauty, art and pleasure as readily as it arrives'],
                'Saturn' => [-1, 'Shani in the 2nd delays wealth and makes speech measured, sparse or slow. Early financial hardship is common, but savings built later prove unusually durable'],
                'Rahu' => [-1, 'Rahu in the 2nd brings unconventional or fluctuating income and a tendency toward exaggeration in speech. Family ties may be disrupted or unusual in form'],
                'Ketu' => [-1, 'Ketu in the 2nd produces hesitant speech and detachment from accumulation. Money comes and goes without leaving much attachment behind, and family bonds feel incomplete'],
            ],
            3 => [
                'Sun' => [1, 'Surya in the 3rd gives courage, initiative and leadership among siblings. Self-made effort is rewarded, and you are not easily intimidated'],
                'Moon' => [1, 'Chandra in the 3rd gives an imaginative, restless mind and close emotional bonds with siblings. Frequent short journeys mark the life'],
                'Mars' => [2, 'Mangal in the 3rd is excellent for courage and enterprise. It grants physical daring, competitive drive and success through sustained personal effort, though friction with siblings is likely'],
                'Mercury' => [2, 'Budha in the 3rd is strong for writing, communication and commerce. The mind is quick, the hands skilful, and networks form easily'],
                'Jupiter' => [0, 'Guru in the 3rd gives wise counsel and supportive siblings, though it can incline toward advising others rather than acting yourself'],
                'Venus' => [1, 'Shukra in the 3rd gives artistic talent, pleasant communication and enjoyable travel. Relations with siblings are affectionate'],
                'Saturn' => [1, 'Shani in the 3rd grants remarkable persistence. Courage is not instinctive but is built through discipline, and this placement outlasts more naturally bold charts'],
                'Rahu' => [1, 'Rahu in the 3rd gives bold, unconventional initiative and skill in modern media or technology. Ambition drives frequent movement'],
                'Ketu' => [0, 'Ketu in the 3rd produces hesitant initiative alongside sharp intuitive insight. Sibling relationships may be distant or karmically complicated'],
            ],
            4 => [
                'Sun' => [-1, 'Surya in the 4th brings pride in home and property but disturbs domestic peace. The relationship with the mother carries tension, and inner contentment is hard-won'],
                'Moon' => [2, 'Chandra in the 4th holds full directional strength and is among the best placements for inner happiness. Deep bond with the mother, attachment to home and land, and genuine emotional security'],
                'Mars' => [-2, 'Mangal in the 4th disturbs domestic peace and brings conflict over property or within the household. Inner restlessness is chronic, and the home rarely feels settled'],
                'Mercury' => [1, 'Budha in the 4th gives a well-educated mind, comfort in the home and possible income through property or vehicles'],
                'Jupiter' => [2, 'Guru in the 4th grants happiness, education, property and a protective maternal influence. One of the most contented placements in the chart'],
                'Venus' => [2, 'Shukra in the 4th gives a beautiful home, vehicles, comfort and a loving mother. Domestic life is a genuine source of pleasure'],
                'Saturn' => [-2, 'Shani in the 4th brings emotional coldness in the home and difficulty with the mother or with property. Inner loneliness persists even amid outward success, and happiness must be constructed rather than inherited'],
                'Rahu' => [-1, 'Rahu in the 4th unsettles the home, often indicating residence far from birthplace and a complicated maternal relationship. Domestic peace is elusive'],
                'Ketu' => [-1, 'Ketu in the 4th produces detachment from home and family, with a sense of rootlessness. Property matters remain unresolved'],
            ],
            5 => [
                'Sun' => [1, 'Surya in the 5th gives intelligence, creative authority and pride in children. Leadership in education or speculation, though ego can distort judgement'],
                'Moon' => [1, 'Chandra in the 5th gives emotional creativity, love of children and an imaginative intellect. Romantic attachments run deep'],
                'Mars' => [0, 'Mangal in the 5th gives sharp technical intelligence and competitive drive in learning, but impulsive speculation and turbulence in romance'],
                'Mercury' => [2, 'Budha in the 5th is excellent for intellect, education and analytical creativity. Learning is rapid and expression clever'],
                'Jupiter' => [2, 'Guru in the 5th is among the finest placements for wisdom, children and past-life merit. It grants ethical intelligence and genuine teaching capacity'],
                'Venus' => [2, 'Shukra in the 5th gives artistic talent, romance and joy through children. Creative expression comes naturally'],
                'Saturn' => [-1, 'Shani in the 5th delays or restricts children and makes learning laborious. What is eventually mastered is mastered thoroughly, but the road is long'],
                'Rahu' => [-1, 'Rahu in the 5th brings unconventional intelligence and risk-taking in speculation. Matters concerning children may be irregular or delayed'],
                'Ketu' => [-1, 'Ketu in the 5th gives intuitive rather than systematic intelligence, with spiritual leanings and concern regarding children'],
            ],
            6 => [
                'Sun' => [1, 'Surya in the 6th grants victory over enemies and strength in competition. Health issues concern the heart or vitality, but adversaries rarely prevail'],
                'Moon' => [-1, 'Chandra in the 6th makes the mind vulnerable to anxiety and the body to psychosomatic complaint. Service to others is genuine but depleting'],
                'Mars' => [2, 'Mangal in the 6th is a strong placement, defeating enemies decisively and giving stamina in conflict. Accidents and inflammatory conditions are the associated risk'],
                'Mercury' => [1, 'Budha in the 6th gives skill in analysis, law, accountancy and health work. You argue well and win disputes through detail'],
                'Jupiter' => [-1, 'Guru in the 6th is a wasted placement for a benefic, though it protects health and grants success against enemies through ethical means rather than force'],
                'Venus' => [-1, 'Shukra in the 6th brings difficulty in relationships and possible health matters involving the reproductive or urinary system. Service is offered in love'],
                'Saturn' => [2, 'Shani in the 6th is genuinely strong, producing endurance that outlasts every opponent. Debts are repaid, enemies exhausted, and chronic conditions managed through discipline'],
                'Rahu' => [2, 'Rahu in the 6th is one of its best placements, destroying enemies and clearing debts. Success in litigation and competitive fields is well indicated'],
                'Ketu' => [1, 'Ketu in the 6th gives healing ability and victory over hidden enemies, though obscure or hard-to-diagnose ailments may appear'],
            ],
            7 => [
                'Sun' => [-1, 'Surya in the 7th brings a proud and authoritative partner, and introduces a contest of standing within the marriage. Ego must be negotiated consciously, or the relationship becomes a competition for primacy'],
                'Moon' => [1, 'Chandra in the 7th gives a caring, emotionally attuned partner and a genuine need for companionship. The marital atmosphere fluctuates with mood, and solitude is poorly tolerated'],
                'Mars' => [-2, 'Mangal in the 7th is the classical Kuja Dosha placement. It brings a forceful partner and friction over dominance, with arguments igniting quickly. Delaying marriage and choosing a partner of comparable temperament materially reduces the difficulty'],
                'Mercury' => [1, 'Budha in the 7th gives an intelligent, articulate and youthful partner. The relationship is sustained by conversation, and may involve business dealings alongside affection'],
                'Jupiter' => [2, 'Guru in the 7th brings a principled, generous and often well-educated partner, and confers real protection on the marriage. Among the strongest indications of a fortunate union'],
                'Venus' => [1, 'Shukra in the 7th brings an attractive, affectionate partner and strong marital happiness, provided the pursuit of pleasure does not outrun commitment'],
                'Saturn' => [-1, 'Shani in the 7th delays marriage and brings a serious, dutiful, frequently older partner. The early years are demanding, but this placement rewards persistence: the bond becomes one of the most durable in the chart'],
                'Rahu' => [-1, 'Rahu in the 7th indicates an unconventional union, often crossing lines of culture, caste, religion or distance. Expectation and reality diverge sharply, and disillusionment follows if the partner was idealised'],
                'Ketu' => [-1, 'Ketu in the 7th produces emotional detachment within partnership and a recurring sense of separateness even in closeness. There may be indifference to marriage itself, or a bond that feels karmic and unchosen'],
            ],
            8 => [
                'Sun' => [-1, 'Surya in the 8th weakens vitality and complicates matters of inheritance and authority. It grants genuine research capacity and interest in hidden subjects'],
                'Moon' => [-1, 'Chandra in the 8th produces emotional volatility, vivid inner life and susceptibility to anxiety. Intuition is powerful; peace of mind is not'],
                'Mars' => [-2, 'Mangal in the 8th brings risk of accident, surgery and sudden reversal. It also grants exceptional investigative capability and courage in crisis. Physical recklessness is the specific danger'],
                'Mercury' => [0, 'Budha in the 8th gives a researching, secretive intelligence suited to investigation, occult study or forensic work. Communication about private matters is guarded'],
                'Jupiter' => [0, 'Guru in the 8th grants a peaceful end, interest in metaphysics and possible gain through inheritance, though it wastes some of its benefic potential here'],
                'Venus' => [-1, 'Shukra in the 8th brings gain through partner or inheritance alongside complications in intimacy. Hidden attachments are possible'],
                'Saturn' => [1, 'Shani in the 8th grants long life and endurance through hardship. Chronic difficulty is likely, but so is the capacity to survive what would break others'],
                'Rahu' => [-1, 'Rahu in the 8th brings sudden upheaval, interest in the occult and unexpected gains or losses. Life includes at least one complete transformation'],
                'Ketu' => [0, 'Ketu in the 8th gives strong occult and intuitive faculty with detachment from material outcomes. Mysterious ailments may occur'],
            ],
            9 => [
                'Sun' => [2, 'Surya in the 9th is highly fortunate, granting a principled father, religious inclination and good fortune through authority and long journeys'],
                'Moon' => [1, 'Chandra in the 9th gives an emotionally open, travelling and devotional temperament. Fortune fluctuates but is generally supportive'],
                'Mars' => [1, 'Mangal in the 9th gives energetic pursuit of principle and success through decisive action abroad, though dogmatism in belief is a risk'],
                'Mercury' => [1, 'Budha in the 9th favours higher learning, philosophy, publishing and teaching. The mind seeks systems of meaning'],
                'Jupiter' => [2, 'Guru in the 9th is among the single most fortunate placements in Vedic astrology. It grants wisdom, ethical clarity, a supportive guru or father, and fortune that arrives without being forced'],
                'Venus' => [2, 'Shukra in the 9th gives fortune through partnership, enjoyable travel and refined philosophical or artistic taste'],
                'Saturn' => [0, 'Shani in the 9th brings a strict or distant father and hard-won fortune. Belief is tested before it is held, which makes it durable'],
                'Rahu' => [-1, 'Rahu in the 9th indicates unconventional beliefs, foreign connections and a complicated relationship with the father or with tradition'],
                'Ketu' => [0, 'Ketu in the 9th gives genuine spiritual inclination and detachment from orthodox religion. The father may be absent or remote'],
            ],
            10 => [
                'Sun' => [2, 'Surya in the 10th is a powerful placement for career. It confers authority, visibility and advancement through position, with genuine capacity for leadership and institutional work'],
                'Moon' => [1, 'Chandra in the 10th gives a public-facing career and fluctuating professional fortune. Work involving people, care or the general public suits you'],
                'Mars' => [2, 'Mangal in the 10th grants drive, executive capability and willingness to take professional risk. It suits engineering, surgery, defence and competitive fields, though it makes you a difficult subordinate'],
                'Mercury' => [1, 'Budha in the 10th favours communication, commerce, analysis and writing in the professional sphere. Several occupations across a lifetime are likely'],
                'Jupiter' => [2, 'Guru in the 10th brings an honourable and respected career, with aptitude for teaching, law or advisory work. Reputation becomes a durable professional asset'],
                'Venus' => [1, 'Shukra in the 10th favours a career in the arts, design, luxury, entertainment or diplomacy. The working environment must be pleasant, or performance falls away'],
                'Saturn' => [1, 'Shani in the 10th produces slow, laborious professional ascent followed by unusually durable standing. Early recognition is withheld; what is eventually built does not collapse'],
                'Rahu' => [1, 'Rahu in the 10th generates strong ambition and can lift you far above your origins, particularly in foreign, technological or unconventional fields. The rise may be rapid and the methods questioned'],
                'Ketu' => [-1, 'Ketu in the 10th brings ambivalence toward worldly success. Achievement arrives but satisfies less than expected, and there is a pull toward work of meaning rather than status'],
            ],
            11 => [
                'Sun' => [2, 'Surya in the 11th gives strong gains, influential friends and fulfilment of ambition through authority. Elder siblings are supportive'],
                'Moon' => [1, 'Chandra in the 11th gives wide social connection and income through the public, though friendships shift with mood'],
                'Mars' => [2, 'Mangal in the 11th is strong for gains through effort and courage. Ambitions are achieved, though friendships can turn competitive'],
                'Mercury' => [2, 'Budha in the 11th is excellent for income through trade, communication and networks. Commercial intelligence is high'],
                'Jupiter' => [2, 'Guru in the 11th grants substantial and ethical gains, influential well-wishers and the fulfilment of major desires'],
                'Venus' => [2, 'Shukra in the 11th gives income through art, beauty or partnership, and a wide circle of pleasant social connections'],
                'Saturn' => [2, 'Shani in the 11th is one of its strongest placements, producing steady accumulating income and gains through older or established associates'],
                'Rahu' => [2, 'Rahu in the 11th is among its best placements, generating large and often unexpected gains, foreign income and powerful networks'],
                'Ketu' => [0, 'Ketu in the 11th limits attachment to gain. Income arrives but is dispersed, and friendships remain few and selective'],
            ],
            12 => [
                'Sun' => [-1, 'Surya in the 12th diminishes outward recognition and may strain the relationship with the father. It favours work behind the scenes, in institutions or abroad'],
                'Moon' => [-1, 'Chandra in the 12th produces a rich inner and dream life alongside emotional isolation. Foreign residence and solitary temperament are indicated'],
                'Mars' => [-1, 'Mangal in the 12th turns aggression inward, producing hidden frustration and secret adversaries. Energy is expended without visible return'],
                'Mercury' => [-1, 'Budha in the 12th gives a reflective, research-oriented mind that communicates poorly in public. Suited to solitary intellectual work'],
                'Jupiter' => [0, 'Guru in the 12th favours spirituality, charity and foreign residence. Material expansion is limited, but liberation and inner growth are genuinely supported'],
                'Venus' => [1, 'Shukra in the 12th is classically strong for comfort in seclusion and for moksha. Pleasures are private, and foreign or hidden attachments are possible'],
                'Saturn' => [0, 'Shani in the 12th brings isolation, heavy expenditure and long periods of quiet labour. It also grants real capacity for renunciation and disciplined solitude'],
                'Rahu' => [0, 'Rahu in the 12th strongly indicates foreign residence, unusual spiritual pursuits and expenditure that is difficult to account for'],
                'Ketu' => [1, 'Ketu in the 12th is its most natural placement, granting genuine detachment, spiritual depth and inclination toward liberation'],
            ],
        ];
    }
}
