<?php

namespace Database\Seeders\Rules;

/**
 * Drishti — what each graha's gaze does to a bhava it aspects.
 *
 * Nine fragments, one per graha. These replace the single generic
 * "adds pressure" sentence the generator used to emit for every
 * malefic and every benefic alike.
 *
 * Written to follow the graha's name, e.g. "The drishti of Shani ...".
 */
class AspectRules
{
    public static function all(): array
    {
        $rows = [];

        foreach (self::map() as $planet => [$polarity, $text]) {
            $rows[] = [
                'condition_type' => 'aspect',
                'condition_key' => $planet,
                'section' => 'modifier',
                'polarity' => $polarity,
                'weight' => 60,
                'text' => $text,
                'conditions' => null,
            ];
        }

        return $rows;
    }

    private static function map(): array
    {
        return [
            'Sun' => [0, 'lends visibility and authority to its affairs, while burning away whatever cannot stand exposure'],
            'Moon' => [1, 'softens its affairs and makes them fluctuate, giving emotional responsiveness where steadiness might be preferred'],
            'Mars' => [-1, 'energises its affairs and makes them contentious. Matters here are achieved by force and rarely without friction'],
            'Mercury' => [1, 'brings intelligence and articulacy to its affairs, though it also keeps them in motion and resists settling them'],
            'Jupiter' => [2, 'protects and expands its affairs. This is the most benefic drishti available, and it substantially repairs damage from elsewhere in the chart'],
            'Venus' => [2, 'refines and smooths its affairs, bringing comfort, harmony and the willing assistance of others'],
            'Saturn' => [-1, 'delays and constrains its affairs. What this bhava governs arrives late, costs more effort than it should, and lasts once it finally arrives'],
            'Rahu' => [-1, 'inflates its affairs and distorts the judgement applied to them. Ambition here outruns the actual circumstances'],
            'Ketu' => [-1, 'withdraws energy from its affairs, producing detachment and a sense of incompleteness in matters that are objectively fine'],
        ];
    }
}
