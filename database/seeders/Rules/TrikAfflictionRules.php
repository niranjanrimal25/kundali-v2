<?php

namespace Database\Seeders\Rules;

/**
 * Grahas in the Trik bhavas (6, 8, 12) under affliction.
 *
 * SUPPLIED BY THE PROJECT OWNER. Encoded verbatim in substance; the only
 * changes are English phrasing and the handling noted below.
 *
 * Each cell of the supplied table carries three facets — physical,
 * relational and temperamental — and they are stored as three separate
 * rules so a reading can use them independently.
 *
 * Two deliberate editorial decisions, both flagged to the owner:
 *
 *  1. HEALTH. These are read as tendencies and vulnerabilities, not
 *     diagnoses. The section carries a disclaimer and the wording avoids
 *     asserting that a named disease is present.
 *  2. SELF-HARM. The source lists suicidal tendencies under an afflicted
 *     Sun. That is encoded, but worded to point toward support rather
 *     than to predict, and it is never stated as an outcome.
 *
 * Firing condition: the graha occupies the 6th, 8th or 12th AND is
 * afflicted — in an enemy or debilitated sign, or conjunct a malefic.
 * The supplied table assumes that affliction; applying it to a
 * well-placed graha in a dusthana would misrepresent the source.
 */
class TrikAfflictionRules
{
    private const PROVENANCE = 'classical';

    private const SOURCE = 'Supplied by project owner';

    public static function all(): array
    {
        $rows = [];

        foreach (self::map() as $planet => $houses) {
            foreach ($houses as $house => $facets) {
                foreach ($facets as $facet => [$polarity, $text]) {
                    $rows[] = [
                        'condition_type' => 'trik_affliction',
                        'condition_key' => "{$planet}:{$house}:{$facet}",
                        'section' => 'afflictions',
                        'category' => match ($facet) {
                            'physical' => 'health',
                            'relations' => 'relationships',
                            default => 'mind',
                        },
                        'polarity' => $polarity,
                        'weight' => 92,
                        'text' => $text,
                        'provenance' => self::PROVENANCE,
                        'source' => self::SOURCE,
                        'conditions' => json_encode([
                            'planet' => $planet,
                            'in_house' => [$house],
                        ]),
                    ];
                }
            }
        }

        return $rows;
    }

    private static function map(): array
    {
        return [
            'Sun' => [
                6 => [
                    'physical' => [-2, 'high fever, irregular heartbeat, spinal pain, a weakened immune system and pitta (bile) disorders'],
                    'relations' => [-1, 'coolness with the father and with the paternal and maternal uncles, and friction with senior officials or with government bodies'],
                    'temperament' => [-1, 'excessive ego and unnecessary anger toward opponents, with disputes at work arising from pride'],
                ],
                8 => [
                    'physical' => [-2, 'weakening of the joints and bones, risk of fracture, trouble in the right eye, strain on the heart, and a burning sensation from excess bile'],
                    'relations' => [-2, 'serious friction over the father\'s health or the relationship with him, and tension with the in-laws'],
                    'temperament' => [-2, 'a deep lack of self-confidence, unnamed fear, self-reproach, fear of losing honour, and spells of despair. Where this weighs heavily, it is worth treating as a reason to seek support rather than something to endure alone'],
                ],
                12 => [
                    'physical' => [-2, 'insomnia, reduced vision or cataract in the right eye, migraine, and a general want of radiance and vitality'],
                    'relations' => [-1, 'distance from the father through living abroad or apart, and differences of outlook with seniors and elders'],
                    'temperament' => [-1, 'excessive isolation, sacrificing one\'s own standing, and loss of reputation or wealth through poor decisions'],
                ],
            ],
            'Moon' => [
                6 => [
                    'physical' => [-2, 'imbalance of the digestive juices, jaundice, chronic phlegm and colds, waterborne intestinal infection, and anaemia'],
                    'relations' => [-1, 'differences with the mother, concern for her health, and obstacles in dealings with maternal aunts'],
                    'temperament' => [-1, 'a fickle mind, difficulty reaching decisions, and frequent swings of mood'],
                ],
                8 => [
                    'physical' => [-2, 'depression, extreme mental strain, insomnia, fear of water, trouble in the left eye, and swelling or oedema'],
                    'relations' => [-2, 'serious distance or hardship involving the mother, and spoiled relations with the in-laws or with female relatives'],
                    'temperament' => [-2, 'mental instability, long blank spells, deep despair, negative thinking and inward fear. Persistent low mood here deserves proper attention rather than endurance'],
                ],
                12 => [
                    'physical' => [-2, 'anxiety and phobia, fluid gathering in the chest or lungs, and phlegm disorders'],
                    'relations' => [-2, 'complete detachment from the mother or maternal relatives, and betrayal by close friends'],
                    'temperament' => [-1, 'excessive emotionality, a tendency to escape rather than face matters, and a wish to withdraw from society'],
                ],
            ],
            'Mars' => [
                6 => [
                    'physical' => [-2, 'high blood pressure, ulcers, liver infection, accidents, physical harm arising from litigation, and torn muscle'],
                    'relations' => [-1, 'disputes and enmity with siblings, particularly younger ones, and with friends'],
                    'temperament' => [-2, 'extreme aggression, a fierce temper, a tendency to quarrel without cause, and little patience'],
                ],
                8 => [
                    'physical' => [-2, 'piles and fistula, internal bleeding, serious accidents, complex surgery, and disease of the private organs'],
                    'relations' => [-2, 'health crisis for a sibling, and severe enmity with brothers-in-law or the in-law side'],
                    'temperament' => [-2, 'stubbornness, violent thoughts, a vengeful cast of mind, and anger that is not controlled'],
                ],
                12 => [
                    'physical' => [-2, 'insomnia, eye injury or reduced vision, injury to the legs, surgery, and extreme physical exhaustion'],
                    'relations' => [-2, 'sharp quarrels with the spouse and a want of marital ease, and broken ties with siblings'],
                    'temperament' => [-1, 'anger expressed covertly, involvement in concealed activity, and regret over hasty decisions'],
                ],
            ],
            'Mercury' => [
                6 => [
                    'physical' => [-1, 'weakness of the nervous system, severe skin allergy, stammering speech, and trouble in the digestive tract'],
                    'relations' => [-1, 'spoiled relations or betrayal involving maternal uncles and aunts, nieces and nephews, or paternal uncles'],
                    'temperament' => [-1, 'getting lost in argument, indecision, confusion in business judgement, and bitterness in speech'],
                ],
                8 => [
                    'physical' => [-2, 'memory loss, stroke, withering of the nerves, and skin disease in the private areas'],
                    'relations' => [-2, 'severe differences with sisters, paternal aunts, or business partners'],
                    'temperament' => [-2, 'being trapped by one\'s own cleverness, deceitful thinking, and a lack of clarity in communication'],
                ],
                12 => [
                    'physical' => [-1, 'nervous insomnia, loss of hearing, and mental fatigue from an overactive mind'],
                    'relations' => [-1, 'growing distance from sisters, nieces and nephews, or friends, and disputes over money'],
                    'temperament' => [-1, 'overthinking, indecision, and a habit of talking more than working'],
                ],
            ],
            'Jupiter' => [
                6 => [
                    'physical' => [-1, 'obesity, fatty liver, diabetes, and disturbance of the digestive system'],
                    'relations' => [-1, 'differences with gurus, teachers, elders and religious guardians'],
                    'temperament' => [-1, 'pride in one\'s own piety, the ego of being learned, looking down on others, and overconfidence'],
                ],
                8 => [
                    'physical' => [-2, 'serious liver disease, gastric trouble and gallstones, and severe ear problems'],
                    'relations' => [-2, 'hardship or difference with children, particularly the first, and distance in outlook from teachers'],
                    'temperament' => [-2, 'moral slackening, scepticism toward spiritual matters, a habit of giving poor advice, and pride in not knowing'],
                ],
                12 => [
                    'physical' => [-1, 'increase in body fat and cholesterol, declining memory, swelling of the feet, and heavy expenditure'],
                    'relations' => [-1, 'distance from children through travel or separation, and a weakened bond with grandparents'],
                    'temperament' => [-1, 'ostentatious religiosity, misuse of wealth in the name of charity, and poor investment'],
                ],
            ],
            'Venus' => [
                6 => [
                    'physical' => [-2, 'kidney infection, diabetes, hormonal imbalance and urinary tract infection'],
                    'relations' => [-2, 'enmity and dispute with the spouse, with female friends, or with a partner'],
                    'temperament' => [-1, 'excessive desire, unethical attraction, neglect of personal care, and a dissatisfied life'],
                ],
                8 => [
                    'physical' => [-2, 'disease of the reproductive organs, poor quality of sperm or ovum, kidney failure, and venereal complaints'],
                    'relations' => [-2, 'a major health crisis for the spouse, and bitterness with the mother-in-law or siblings-in-law'],
                    'temperament' => [-2, 'addiction to sensual pleasure, inclination toward unethical relationships, and false pride in appearance'],
                ],
                12 => [
                    'physical' => [-1, 'reduced eyesight, physical weakness from excessive indulgence, and weakness of the nerves'],
                    'relations' => [-2, 'separation or divorce from the spouse, and betrayal by a lover or female friend'],
                    'temperament' => [-1, 'extravagance, intense attachment to comfort and luxury, and laziness'],
                ],
            ],
            'Saturn' => [
                6 => [
                    'physical' => [-1, 'arthritis, severe pain in the joints and knees, and chronic constipation'],
                    'relations' => [-1, 'dispute and betrayal involving servants, employees, labourers or paternal uncles'],
                    'temperament' => [-1, 'extreme laziness, procrastination, neglect of work, and pessimistic thinking'],
                ],
                8 => [
                    'physical' => [-2, 'paralysis, severe trouble in the legs and teeth, wasting of the nerves, and risk of chronic disability'],
                    'relations' => [-2, 'severe differences with the in-laws, and distance from elderly relatives'],
                    'temperament' => [-2, 'deep sadness, renunciation, stubbornness, aversion to the world, and an overly suspicious nature'],
                ],
                12 => [
                    'physical' => [-2, 'chronic insomnia, chronic depression, severe injury to the leg, and hospitalisation or seclusion'],
                    'relations' => [-2, 'broken ties with paternal uncles, elder brothers or old friends, and complete isolation from society'],
                    'temperament' => [-2, 'a grasping nature, narrow thinking, negativity, and self-inflicted harm through one\'s own actions'],
                ],
            ],
            'Rahu' => [
                6 => [
                    'physical' => [-1, 'problems that resist diagnosis, poisonous bites, and intestinal worms or ulcers'],
                    'relations' => [-1, 'disputes with the maternal side, with the paternal grandfather\'s line, or with people of another community'],
                    'temperament' => [-1, 'diplomatic deceit, suspicious behaviour, a habit of breaking rules, and extreme restlessness'],
                ],
                8 => [
                    'physical' => [-2, 'fear of poisoning, risk of cancer, complex nerve trouble in the private parts, and accidents'],
                    'relations' => [-2, 'major betrayal or sudden rupture with the in-laws, and an increase in hidden enemies'],
                    'temperament' => [-2, 'inclination toward gambling, a wish to earn by unethical means, and a confused mind'],
                ],
                12 => [
                    'physical' => [-2, 'nightmares, phobia, mental delusion, and adverse reaction to drugs'],
                    'relations' => [-1, 'betrayal by people living abroad or by relatives of unusual background'],
                    'temperament' => [-2, 'risk of falling into addiction, living in delusion, extreme insomnia and mental instability'],
                ],
            ],
            'Ketu' => [
                6 => [
                    'physical' => [-1, 'intestinal worms, illness caused by microscopic organisms, and sudden marks on the skin'],
                    'relations' => [-1, 'dissatisfaction and distance with nieces and nephews, grandchildren, or household help'],
                    'temperament' => [-1, 'a feeling of detachment, lack of interest in work, and a sudden whimsical turn'],
                ],
                8 => [
                    'physical' => [-2, 'lower back and spinal trouble, sudden surgery, and internal pain that resists identification'],
                    'relations' => [-2, 'severe dispute with relatives over the maternal grandmother\'s or ancestral property'],
                    'temperament' => [-1, 'mysterious behaviour, distrust of everyone, and sudden thoughts of walking away'],
                ],
                12 => [
                    'physical' => [-1, 'injury or burning on the soles of the feet, sudden disturbance of the nervous system, insomnia and weakness'],
                    'relations' => [-2, 'mental distance from all family members, and spiritual or social withdrawal'],
                    'temperament' => [-1, 'a feeling of hollowness, avoidance of worldly responsibility, and a meditative but indifferent cast of mind'],
                ],
            ],
        ];
    }
}
