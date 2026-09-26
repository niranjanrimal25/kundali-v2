<?php

namespace Database\Seeders\Rules;

/**
 * Bhava x Rashi — how each house expresses through the sign upon it.
 * 12 x 12 = 144 fragments.
 *
 * This is the layer the user's own example described: "1st house in
 * Aries (fire), accounting for Digbala and direction, indicates
 * self-undoing, aggression, misplaced anger, impulsive decisions and
 * high self-confidence."
 */
class HouseSignRules
{
    public static function all(): array
    {
        $rows = [];

        foreach (self::map() as $house => $signs) {
            foreach ($signs as $sign => $text) {
                $rows[] = [
                    'condition_type' => 'house_sign',
                    'condition_key' => "{$house}:{$sign}",
                    'section' => "house_{$house}",
                    'polarity' => 0,
                    'weight' => 80,
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
                0 => 'The body is lean and active, the bearing direct, and the approach to life confrontational rather than negotiated. Self-assertion comes easily and self-restraint does not, which inclines toward impulsive decisions, misplaced anger and a self-undoing haste that is the chief thing to govern',
                1 => 'The constitution is solid and the manner unhurried. You present as dependable and immovable, and you resist being rushed by anyone',
                2 => 'The build is slender and the manner animated. You define yourself through speech and ideas, and adapt your presentation to whoever is in front of you',
                3 => 'The face is round and the presence soft. You lead with feeling rather than argument, and your sense of self rests on emotional security',
                4 => 'The bearing is upright and noticeable. You occupy space naturally and expect to be treated with regard',
                5 => 'The build is neat and the manner precise. You present as competent and reserved, and judge yourself by standards no one else has set',
                6 => 'The features are pleasing and the manner accommodating. You define yourself substantially through relationship, and are uncomfortable in open conflict',
                7 => 'The gaze is direct and the presence guarded. You reveal little and observe much, and your self-understanding runs deeper than you disclose',
                8 => 'The frame is tall or expansive and the manner frank. You present as confident and are inclined to say what others are only thinking',
                9 => 'The build is spare and the expression serious. You appear older than your years early in life and lighter later, and self-worth is tied to what you have built',
                10 => 'The bearing is distinctive and slightly apart. You present as friendly yet unreachable, and resist being categorised',
                11 => 'The eyes are expressive and the manner yielding. Your sense of self is fluid and takes colour from your surroundings',
            ],
            2 => [
                0 => 'Wealth is earned aggressively and spent quickly. Speech is blunt and direct, and family relations carry a combative edge',
                1 => 'Money accumulates steadily and is held securely. The voice is pleasant, the taste refined, and family is a source of genuine stability',
                2 => 'Income arrives through several channels rather than one. Speech is quick and persuasive, and family relations are talkative but changeable',
                3 => 'Wealth fluctuates with circumstance. Speech is gentle and emotionally attuned, and family bonds run deep',
                4 => 'Wealth is tied to status and displayed rather than hidden. Speech carries authority, and family pride is strong',
                5 => 'Money is managed carefully and accounted precisely. Speech is exact, sometimes critical, and family matters are handled practically',
                6 => 'Wealth comes through partnership and diplomacy. The voice is pleasing, and family harmony is actively maintained',
                7 => 'Finances are private and rarely disclosed. Speech is deliberate and can wound, and family relations carry undercurrents',
                8 => 'Wealth is generous in flow and generous in spending. Speech is honest to the point of bluntness, and family is philosophical in outlook',
                9 => 'Money is accumulated slowly and guarded strictly. Speech is sparse and measured, and family duty weighs heavily',
                10 => 'Income arrives unpredictably and often unconventionally. Speech is original, and family relations are unorthodox',
                11 => 'Finances are poorly tracked and easily dispersed. Speech is imaginative and kind, and family bonds are compassionate but unclear',
            ],
            3 => [
                0 => 'Courage is instinctive and confrontational. Siblings are assertive, and you advance by direct initiative',
                1 => 'Effort is persistent rather than rapid. Siblings are dependable, and progress comes by steady accumulation',
                2 => 'The mind is quick and communication is your strongest instrument. Siblings are numerous or influential in your life',
                3 => 'Courage is emotional rather than physical. Bonds with siblings are close, and frequent short journeys mark the life',
                4 => 'Initiative is confident and visible. You lead among siblings and peers, and enjoy recognition for your efforts',
                5 => 'Effort is methodical and detail-driven. Communication is precise, and siblings are practical in temperament',
                6 => 'Courage is expressed through negotiation rather than force. Sibling relations are harmonious and diplomatic',
                7 => 'Effort is intense and sustained. Communication is guarded, and sibling relations carry hidden depth or rivalry',
                8 => 'Initiative is broad and optimistic. Travel is frequent, and siblings share your philosophical outlook',
                9 => 'Courage is disciplined and slow to appear. Sibling relations are dutiful rather than warm, and effort is long-sustained',
                10 => 'Initiative is unconventional and independent. Communication is original, and siblings are unusual in character',
                11 => 'Courage fluctuates with emotional state. Communication is intuitive, and sibling bonds are compassionate but indistinct',
            ],
            4 => [
                0 => 'The home is active and occasionally turbulent. Property is acquired by effort, and the mother is strong-willed',
                1 => 'The home is comfortable, well-furnished and permanent. Land is held securely, and the mother is nurturing and steady',
                2 => 'The home is busy and full of movement, possibly with more than one residence. The mother is communicative and youthful in manner',
                3 => 'The home is the emotional centre of life. Deep attachment to mother and birthplace, and real contentment found indoors',
                4 => 'The home is impressive and a matter of pride. The mother is dignified, and domestic authority is clearly held',
                5 => 'The home is orderly and practical. The mother is capable and exacting, and domestic matters are efficiently managed',
                6 => 'The home is beautiful and socially active. The mother is refined, and domestic harmony is highly valued',
                7 => 'The home is private and guarded. Emotional undercurrents run through the household, and the mother is intense',
                8 => 'The home is open, expansive and possibly far from where you began. The mother is philosophical and encouraging',
                9 => 'The home is austere and responsibility-laden. Property comes late, and the mother is dutiful but emotionally reserved',
                10 => 'The home is unconventional and often distant from the birthplace. The mother is independent-minded',
                11 => 'The home is peaceful but poorly defined. Emotional boundaries are porous, and the mother is compassionate and sacrificing',
            ],
            5 => [
                0 => 'Intelligence is quick and competitive. Children are energetic, and creative expression is bold and immediate',
                1 => 'Intelligence is retentive and practical. Children are steady, and creative work is patient and material',
                2 => 'Intelligence is verbal and versatile. Children are bright and talkative, and creativity expresses through words',
                3 => 'Intelligence is intuitive and imaginative. Strong emotional bond with children, and creativity flows from feeling',
                4 => 'Intelligence is authoritative and confident. Pride in children is marked, and creative expression seeks an audience',
                5 => 'Intelligence is analytical and exacting. Children are studious, and creativity is disciplined rather than spontaneous',
                6 => 'Intelligence is balanced and aesthetic. Children are sociable, and creativity is expressed through art and design',
                7 => 'Intelligence is penetrating and investigative. Relations with children carry intensity, and creativity explores hidden subjects',
                8 => 'Intelligence is philosophical and wide-ranging. Children are independent, and creativity is tied to meaning and belief',
                9 => 'Intelligence is structured and slow-maturing. Children arrive late or carry responsibility, and creativity is disciplined',
                10 => 'Intelligence is original and unorthodox. Children are independent-minded, and creativity breaks with convention',
                11 => 'Intelligence is intuitive and impressionable. Children are sensitive, and creativity is imaginative rather than structured',
            ],
            6 => [
                0 => 'Enemies are confronted directly and usually defeated. Health issues are acute and inflammatory rather than chronic',
                1 => 'Opposition is outlasted rather than overcome. Health concerns affect the throat and neck, and work habits are steady',
                2 => 'Disputes are argued and negotiated. Health issues involve the nerves and respiration, and work is varied',
                3 => 'Conflict is felt emotionally and avoided where possible. Health concerns affect the stomach and digestion',
                4 => 'Opposition is met with authority. Health issues concern the heart and vitality, and you expect to lead at work',
                5 => 'Disputes are handled through detail and precision. Health concerns involve digestion and anxiety, and service is conscientious',
                6 => 'Conflict is resolved by negotiation. Health issues concern the kidneys and balance, and partnership matters at work',
                7 => 'Enemies are hidden and dealt with decisively. Health concerns are chronic or difficult to diagnose',
                8 => 'Disputes are approached on principle. Health issues affect the hips and liver, and work carries an ethical dimension',
                9 => 'Opposition is endured rather than fought. Health concerns involve bones and joints, and the work ethic is severe',
                10 => 'Conflict is handled unconventionally. Health issues affect circulation and the nervous system, and work must have purpose',
                11 => 'Enemies are elusive and difficulties poorly defined. Health concerns involve the feet, immunity and sensitivity to medication',
            ],
            7 => [
                0 => 'The partner is assertive, energetic and independent, and the relationship carries a competitive charge. Conflict arrives early and openly rather than festering',
                1 => 'The partner is steady, sensual and materially grounded, and the union tends toward permanence once formed. Disagreement is rare but immovable when it comes',
                2 => 'The partner is communicative, youthful in manner and intellectually engaging. Two distinct phases or influences often mark the marital life',
                3 => 'The partner is nurturing, emotionally attuned and family-centred, and the home becomes the centre of the relationship. Moods govern the atmosphere more than events do',
                4 => 'The partner is dignified, proud and accustomed to being regarded. The relationship requires that both parties be seen, and suffers when one is eclipsed',
                5 => 'The partner is discerning, practical and helpful, though inclined to correct. Service is offered generously and criticism accompanies it',
                6 => 'The partner is refined, socially adept and genuinely committed to fairness. Harmony is prized, sometimes at the cost of saying what is true',
                7 => 'The partner is intense, private and emotionally deep. The bond is powerful and transformative, but jealousy and control must be consciously managed',
                8 => 'The partner is independent, philosophical and possibly of different background or distant origin. Freedom within the union is a condition of its survival',
                9 => 'The partner is mature, responsible and reserved, often older or weightier in manner. The marriage strengthens with time rather than beginning at its peak',
                10 => 'The partner is unconventional, independent-minded and resistant to ordinary expectation. The relationship works on its own terms or not at all',
                11 => 'The partner is compassionate, imaginative and emotionally receptive. Idealisation is a risk: seeing who you wish for rather than who is present',
            ],
            8 => [
                0 => 'Transformation arrives abruptly and violently. Risk of accident or surgery, and a temperament that recovers quickly from shock',
                1 => 'Change is resisted and therefore arrives slowly and heavily. Inheritance and joint finances are significant themes',
                2 => 'Upheaval comes through communication, contracts or siblings. The mind is drawn to research and hidden information',
                3 => 'Crisis is felt emotionally and deeply. Strong intuition, vivid dreams, and attachment to what has been lost',
                4 => 'Transformation affects status and pride. Matters of inheritance involve authority figures, and recovery is dignified',
                5 => 'Upheaval is analysed rather than felt. Health crises are investigated thoroughly, and you master the details of difficulty',
                6 => 'Crisis arrives through partnership and shared resources. Joint finances require careful documentation',
                7 => 'This is the natural seat of the 8th. Transformation is profound, occult capacity genuine, and resilience considerable',
                8 => 'Upheaval broadens rather than destroys. Crisis leads to philosophical growth, and foreign matters affect inheritance',
                9 => 'Change is slow, structural and permanent. Longevity is generally good, and hardship is endured rather than escaped',
                10 => 'Transformation is sudden and unconventional. Interest in unorthodox research, technology or the fringes of knowledge',
                11 => 'Crisis dissolves rather than breaks. Strong mystical inclination, and boundaries between self and circumstance blur under pressure',
            ],
            9 => [
                0 => 'Belief is held forcefully and defended actively. The father is assertive, and travel is undertaken boldly',
                1 => 'Faith is traditional and unchanging. The father is stable and providing, and fortune accumulates slowly',
                2 => 'Belief is questioned, discussed and revised. The father is communicative, and learning is broad rather than deep',
                3 => 'Faith is emotional and inherited from family. The father is caring, and fortune fluctuates with circumstance',
                4 => 'Belief is confident and authoritative. The father is dignified and influential, and fortune is tied to status',
                5 => 'Faith is examined critically before it is accepted. The father is practical, and fortune follows careful effort',
                6 => 'Belief is balanced and tolerant. The father is refined, and fortune arrives through partnerships',
                7 => 'Faith is intense and privately held. The father is complex, and fortune changes dramatically at intervals',
                8 => 'This is the natural seat of the 9th. Faith is genuine and expansive, the father supportive, and fortune strong',
                9 => 'Belief is austere and duty-bound. The father is strict or distant, and fortune arrives late but solidly',
                10 => 'Faith is unorthodox and independently reasoned. The father is unconventional, and fortune comes through unusual channels',
                11 => 'Belief is mystical and compassionate. The father is gentle or absent, and fortune is intuitive rather than planned',
            ],
            10 => [
                0 => 'The career demands initiative and tolerance of risk, favouring engineering, surgery, defence, athletics or any field rewarding decisive action. You work badly under close supervision',
                1 => 'The career favours finance, land, food, luxury goods, music or any field where value is built and held. Advancement is slow, steady and durable',
                2 => 'The career favours writing, teaching, trade, media, analysis and negotiation. Multiple occupations or parallel income streams are likely rather than one linear path',
                3 => 'The career favours care, hospitality, property, food, nursing or public service. Work must feel emotionally meaningful or it cannot be sustained',
                4 => 'The career favours authority, administration, government, medicine or public prominence. You require recognition and function poorly when anonymous',
                5 => 'The career favours analysis, health, accountancy, editing, research and systems. Competence is the reward sought, more than status',
                6 => 'The career favours law, diplomacy, design, counselling, the arts and any work conducted through partnership. You perform best in collaboration',
                7 => 'The career favours research, investigation, surgery, psychology, insurance or occult study. You are drawn to what is hidden and unafraid of crisis',
                8 => 'The career favours teaching, law, publishing, religion, higher education and advisory work. Ethical alignment matters more to you than remuneration',
                9 => 'The career favours administration, construction, mining, governance and long-horizon institutional work. Recognition arrives late but does not then depart',
                10 => 'The career favours technology, research, social reform, aviation and unconventional fields. Rigid hierarchy is intolerable to you',
                11 => 'The career favours healing, imagery, music, charity, spirituality or work near water. Commercial ruthlessness does not come naturally',
            ],
            11 => [
                0 => 'Gains come rapidly through initiative. Friends are energetic and competitive, and ambitions are pursued aggressively',
                1 => 'Income accumulates steadily and permanently. Friends are loyal and long-standing, and desires are material and concrete',
                2 => 'Gains arrive through multiple channels and communication. The social network is wide but loosely held',
                3 => 'Income fluctuates with circumstance. Friendships are emotionally close, and ambitions are tied to family security',
                4 => 'Gains come through status and authority. Friends are influential, and ambitions are large and visible',
                5 => 'Income is earned through skill and precision. Friends are useful rather than intimate, and ambitions are realistic',
                6 => 'Gains arrive through partnership and social grace. The network is broad, cultivated and genuinely enjoyed',
                7 => 'Income comes from hidden or joint sources. Friendships are few, deep and privately held',
                8 => 'Gains are generous and philosophically motivated. Friends are drawn from distant places or different backgrounds',
                9 => 'Income grows slowly but never reverses. Friends are older or more established, and ambitions mature with time',
                10 => 'This is the natural seat of the 11th. Gains come through networks, technology and collective enterprise, and friendships are unconventional',
                11 => 'Income is irregular and hard to track. Friendships are compassionate and boundaries indistinct, and ambitions are idealistic',
            ],
            12 => [
                0 => 'Expenditure is impulsive and energetic. Sleep is disturbed, and hidden adversaries are confronted directly when discovered',
                1 => 'Losses are material and slow to recover. Expenditure is on comfort, and foreign matters concern property or finance',
                2 => 'Expenditure is scattered across many small outlets. The mind is restless in solitude, and foreign travel is frequent',
                3 => 'Loss is felt emotionally. Strong dream life, attachment to the past, and foreign residence connected to family',
                4 => 'Expenditure is on status and display. Isolation wounds pride, and foreign connections involve authority',
                5 => 'Expenditure is on health and service. Anxiety disturbs sleep, and hidden matters are analysed obsessively',
                6 => 'Losses occur through partnership. Solitude is poorly tolerated, and foreign matters involve relationships',
                7 => 'Expenditure is hidden and undisclosed. Deep unconscious life, strong occult capacity, and secret adversaries',
                8 => 'Expenditure is on travel, religion and charity. Foreign residence is well indicated, and solitude is philosophically productive',
                9 => 'Losses are structural and enduring. Isolation is chosen as much as imposed, and expenditure is grudging',
                10 => 'Expenditure is unconventional and unpredictable. Foreign connections are technological or humanitarian',
                11 => 'This is the natural seat of the 12th. Genuine capacity for renunciation, spiritual depth, and dissolution of boundaries between self and world',
            ],
        ];
    }
}
