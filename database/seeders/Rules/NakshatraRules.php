<?php

namespace Database\Seeders\Rules;

/**
 * Janma Nakshatra — the Moon's lunar mansion at birth.
 * 27 fragments, keyed by nakshatra index 0..26.
 *
 * In practice this is weighted at least as heavily as the Lagna for
 * reading temperament, since it describes the mind rather than the body.
 *
 * Written to follow the nakshatra's name in the overview section.
 */
class NakshatraRules
{
    public static function all(): array
    {
        $rows = [];

        foreach (self::map() as $index => [$polarity, $text]) {
            $rows[] = [
                'condition_type' => 'nakshatra',
                'condition_key' => (string) $index,
                'section' => 'overview',
                'polarity' => $polarity,
                'weight' => 88,
                'text' => $text,
                'conditions' => null,
            ];
        }

        return $rows;
    }

    private static function map(): array
    {
        return [
            0 => [0, 'This is the nakshatra of beginnings, and it gives speed, restlessness and a strong impulse to start before conditions are ready. You recover from setbacks faster than most people and you also create more of them'],
            1 => [1, 'This nakshatra gives magnetism, a strong aesthetic sense and genuine charm. There is real creative ability alongside a susceptibility to indulgence and a dislike of restriction'],
            2 => [-1, 'This is a fierce nakshatra, giving intensity, sharp perception and a capacity for sudden anger. Achievement comes readily; steadiness does not, and relationships bear the cost'],
            3 => [1, 'This nakshatra gives creative fertility, nurturing instinct and strong sexual and artistic energy. You produce a great deal, and you need a settled base from which to do it'],
            4 => [0, 'This nakshatra gives a searching, questioning mind drawn to hidden and difficult knowledge. There is brilliance here, alongside a restlessness that resists ordinary contentment'],
            5 => [-1, 'This is the most severe nakshatra, ruled by Rudra. It gives penetrating insight and real transformative power, but its lessons arrive through loss. Anger and grief are the material you work with'],
            6 => [1, 'This nakshatra gives sociability, refinement and skill in relationship. You are well liked and prosper through others, though you depend on their approval more than you admit'],
            7 => [2, 'This is the most auspicious nakshatra for stability and nourishment. It gives a caring, dutiful, protective nature and a strong instinct to provide for others before yourself'],
            8 => [-1, 'This nakshatra gives deep intuition, secrecy and a penetrating emotional intelligence. It also carries an undercurrent of grievance that is slow to release'],
            9 => [-1, 'This is a fierce nakshatra of ancestry and authority. It gives leadership, rebelliousness and a determination that borders on ruthlessness when crossed'],
            10 => [0, 'This nakshatra gives ambition, sociability and a strong desire for status and comfort. Effort is sustained where the reward is visible'],
            11 => [1, 'This nakshatra gives a cheerful, orderly, service-minded nature with genuine organisational ability. You bring structure to whatever you touch'],
            12 => [1, 'This nakshatra gives charm, sociability and skill in partnership and trade. You are adaptable and read a room quickly'],
            13 => [1, 'This nakshatra gives artistic refinement, a love of beauty and considerable personal magnetism, alongside a strong reaction against anything ugly or coarse'],
            14 => [0, 'This nakshatra gives ambition, restlessness and a competitive drive toward achievement. Satisfaction is deferred, because the target moves once reached'],
            15 => [1, 'This nakshatra gives sociability, negotiating skill and a genuine ability to balance opposing interests. You prosper through diplomacy'],
            16 => [1, 'This nakshatra gives warmth, friendliness and a strong need for companionship. You are generous and you take betrayal very hard'],
            17 => [-1, 'This nakshatra gives sharp insight and a piercing, critical intelligence. It carries a tendency toward obsession and a capacity for deep-held resentment'],
            18 => [-1, 'This is a fierce nakshatra of ancestry. It gives determination, endurance and considerable capacity for hard, unglamorous achievement, along with a struggle against self-imposed limitation'],
            19 => [0, 'This nakshatra gives conviction, philosophical seriousness and a strong sense of what is right. It also gives an inflexibility that makes compromise genuinely difficult'],
            20 => [2, 'This nakshatra gives integrity, perseverance and an unusual capacity to finish what is begun. Victory here is permanent rather than showy, and leadership is earned rather than claimed'],
            21 => [2, 'This nakshatra gives an optimistic, energetic and genuinely lucky nature. You listen well, learn from others, and recover from difficulty with unusual speed'],
            22 => [1, 'This nakshatra gives ambition, wealth-generating capacity and strong rhythmic or musical sense. There is real drive here and a tendency to measure yourself by what you own'],
            23 => [0, 'This nakshatra gives healing ability, philosophical depth and a mind drawn to what lies beneath the surface. It also brings periods of genuine isolation'],
            24 => [1, 'This nakshatra gives wisdom, patience and spiritual depth. You work best in seclusion, and worldly ambition matters less to you than it does to others'],
            25 => [1, 'This nakshatra gives kindness, imagination and prosperity that arrives without much grasping. Support appears when needed, which can encourage passivity'],
            26 => [2, 'This is the final nakshatra, giving compassion, sacrifice and a strong pull toward completion and release. Material ambition is weak and intuitive and spiritual capacity is unusually strong'],
        ];
    }
}
