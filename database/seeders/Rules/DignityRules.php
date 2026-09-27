<?php

namespace Database\Seeders\Rules;

/**
 * Avastha — what a given dignity means for a specific graha.
 * 9 grahas x 7 dignity states = 63 fragments.
 *
 * The generic dignity lines in CoreRules say the same thing about every
 * planet ("is exalted here, giving its significations unusual strength").
 * These say what THIS graha's strength or weakness actually costs you.
 *
 * Higher weight than the generic versions, so they win the lookup where
 * they exist. Written to follow "It ...".
 */
class DignityRules
{
    private const STATES = ['exalted', 'moolatrikona', 'own', 'friendly', 'neutral', 'enemy', 'debilitated'];

    public static function all(): array
    {
        $rows = [];

        foreach (self::map() as $planet => $states) {
            foreach ($states as $state => [$polarity, $text]) {
                $rows[] = [
                    'condition_type' => 'dignity_planet',
                    'condition_key' => "{$planet}:{$state}",
                    'section' => 'modifier',
                    'polarity' => $polarity,
                    'weight' => 95,
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
                'exalted' => [2, 'is exalted, so authority, vitality and confidence are available to you without having to be manufactured'],
                'moolatrikona' => [2, 'occupies its moolatrikona, giving strong health, clear authority and a father who provided'],
                'own' => [2, 'rests in its own sign, so your sense of your own worth does not depend on other people confirming it'],
                'friendly' => [1, 'sits in a friendly sign, so authority is granted readily and rarely contested'],
                'neutral' => [0, 'is neutrally placed, so status is neither given nor withheld and must simply be worked for'],
                'enemy' => [-1, 'sits in an inimical sign, so recognition has to be argued for. Authority is questioned even where it is deserved'],
                'debilitated' => [-2, 'is debilitated, which undercuts confidence at the root. Capability is real but self-belief lags behind it, and the relationship with the father is usually where this begins'],
            ],
            'Moon' => [
                'exalted' => [2, 'is exalted, giving emotional steadiness and a settled inner base that holds under pressure'],
                'moolatrikona' => [2, 'occupies its moolatrikona, giving a calm, nourishing mind and a supportive mother'],
                'own' => [2, 'rests in its own sign, so emotional security is native to you rather than borrowed from circumstances'],
                'friendly' => [1, 'sits in a friendly sign, giving a generally contented mind that recovers well from upset'],
                'neutral' => [0, 'is neutrally placed, so emotional weather follows circumstance rather than setting it'],
                'enemy' => [-1, 'sits in an inimical sign, so the mind is restless and contentment is harder to reach than it should be'],
                'debilitated' => [-2, 'is debilitated, which is the single most consequential weakness a chart can carry. Emotional resilience is low, moods run deep and long, and deliberate mental discipline is not optional here'],
            ],
            'Mars' => [
                'exalted' => [2, 'is exalted, so energy is disciplined and sustained rather than merely abundant'],
                'moolatrikona' => [2, 'occupies its moolatrikona, giving courage that is available on demand and does not need provoking'],
                'own' => [2, 'rests in its own sign, so initiative and physical stamina are dependable'],
                'friendly' => [1, 'sits in a friendly sign, giving effective, well-directed drive'],
                'neutral' => [0, 'is neutrally placed, so energy is adequate and neither a strength nor a liability'],
                'enemy' => [-1, 'sits in an inimical sign, so effort meets resistance and frustration converts into temper'],
                'debilitated' => [-2, 'is debilitated, so anger goes underground instead of discharging. Direct confrontation is avoided and resentment accumulates, which is the harder pattern to unwind'],
            ],
            'Mercury' => [
                'exalted' => [2, 'is exalted, giving exceptional analytical precision and genuine command of detail'],
                'moolatrikona' => [2, 'occupies its moolatrikona, giving articulate intelligence and sound commercial judgement'],
                'own' => [2, 'rests in its own sign, so learning and communication are reliable strengths'],
                'friendly' => [1, 'sits in a friendly sign, giving quick comprehension and easy expression'],
                'neutral' => [0, 'is neutrally placed, so intellect is serviceable and unremarkable'],
                'enemy' => [-1, 'sits in an inimical sign, so thinking is scattered and important details are missed under pressure'],
                'debilitated' => [-2, 'is debilitated, so judgement outruns evidence. Conclusions are reached confidently and revised later, and contracts and documents deserve a second reader'],
            ],
            'Jupiter' => [
                'exalted' => [2, 'is exalted, giving genuine wisdom and real protective power over whatever it touches'],
                'moolatrikona' => [2, 'occupies its moolatrikona, giving sound ethical judgement and support from teachers'],
                'own' => [2, 'rests in its own sign, so faith, learning and generosity operate without obstruction'],
                'friendly' => [1, 'sits in a friendly sign, giving good judgement and timely help from well-disposed people'],
                'neutral' => [0, 'is neutrally placed, so its protective influence is present but not decisive'],
                'enemy' => [-1, 'sits in an inimical sign, so optimism is thin and guidance arrives late or from the wrong people'],
                'debilitated' => [-2, 'is debilitated, which is costly because Guru is the chart\'s principal protector. Faith is replaced by calculation, generosity by caution, and the usual cushioning against difficulty is absent'],
            ],
            'Venus' => [
                'exalted' => [2, 'is exalted, giving refined taste, artistic capability and genuine devotion in love'],
                'moolatrikona' => [2, 'occupies its moolatrikona, giving harmonious relationships and comfortable circumstances'],
                'own' => [2, 'rests in its own sign, so affection and aesthetic sense operate naturally'],
                'friendly' => [1, 'sits in a friendly sign, giving ease in relationship and a pleasant standard of living'],
                'neutral' => [0, 'is neutrally placed, so comfort and affection track circumstance rather than shaping it'],
                'enemy' => [-1, 'sits in an inimical sign, so relationships require more management than they should and satisfaction is elusive'],
                'debilitated' => [-2, 'is debilitated, so love is analysed rather than enjoyed. The instinct is to improve the beloved rather than accept them, and that instinct costs more than it returns'],
            ],
            'Saturn' => [
                'exalted' => [2, 'is exalted, giving fairness, endurance and the capacity to build things that outlast you'],
                'moolatrikona' => [2, 'occupies its moolatrikona, giving disciplined ambition and steady, permanent progress'],
                'own' => [2, 'rests in its own sign, so patience and structure are strengths rather than burdens'],
                'friendly' => [1, 'sits in a friendly sign, so delay is real but proportionate and the reward does arrive'],
                'neutral' => [0, 'is neutrally placed, so obstruction is ordinary rather than severe'],
                'enemy' => [-1, 'sits in an inimical sign, so delay is disproportionate and effort exceeds reward for long stretches'],
                'debilitated' => [-2, 'is debilitated, so patience and impulse are permanently at odds. Work is begun forcefully and sustained badly, and the resulting frustration is the chief thing to manage'],
            ],
            'Rahu' => [
                'exalted' => [1, 'is well placed, so ambition is channelled productively rather than merely inflated'],
                'moolatrikona' => [1, 'is strongly placed, giving unconventional advantage in worldly matters'],
                'own' => [1, 'is comfortably placed, so its hunger produces achievement rather than agitation'],
                'friendly' => [1, 'sits in a friendly sign, so its ambition finds legitimate outlets'],
                'neutral' => [0, 'is neutrally placed, so its distorting influence is moderate'],
                'enemy' => [-1, 'sits in an inimical sign, so ambition outruns judgement and shortcuts become tempting'],
                'debilitated' => [-2, 'is poorly placed, so desire attaches to what cannot satisfy it. Illusion and misdirected effort are the recurring pattern'],
            ],
            'Ketu' => [
                'exalted' => [1, 'is well placed, so detachment becomes genuine insight rather than avoidance'],
                'moolatrikona' => [1, 'is strongly placed, giving intuitive penetration without the usual sense of loss'],
                'own' => [1, 'is comfortably placed, so its separative quality works as clarity rather than deprivation'],
                'friendly' => [1, 'sits in a friendly sign, so what it withdraws from you do not much miss'],
                'neutral' => [0, 'is neutrally placed, so its detaching influence is mild'],
                'enemy' => [-1, 'sits in an inimical sign, so its withdrawal is felt as deprivation in matters you actually wanted'],
                'debilitated' => [-2, 'is poorly placed, so detachment arrives as confusion rather than release, and the affairs it touches feel unfinished for years'],
            ],
        ];
    }

    /** @return list<string> */
    public static function states(): array
    {
        return self::STATES;
    }
}
