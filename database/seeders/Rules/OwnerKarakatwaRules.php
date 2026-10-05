<?php

namespace Database\Seeders\Rules;

/**
 * Karakatwa — what each graha signifies.
 *
 * SUPPLIED BY THE PROJECT OWNER, encoded verbatim in substance.
 *
 * These are deliberately more specific than the significations this
 * project had written for itself: the owner's lists name wheat, bone
 * marrow, the left eye for males, and the soul, none of which the
 * original synthesis mentioned.
 *
 * Facets are stored separately so a reading can pull only what it
 * needs — the body facet for a health passage, the relations facet
 * for a family passage.
 *
 * Only Sun, Moon and Mars were supplied. The remaining six grahas are
 * intentionally absent rather than invented; they will read from this
 * layer as soon as the owner supplies them.
 */
class OwnerKarakatwaRules
{
    private const PROVENANCE = 'classical';

    private const SOURCE = 'Supplied by project owner';

    public static function all(): array
    {
        $rows = [];

        foreach (self::map() as $planet => $facets) {
            foreach ($facets as $facet => $text) {
                $rows[] = [
                    'condition_type' => 'karakatwa',
                    'condition_key' => "{$planet}:{$facet}",
                    'section' => 'karakatwa',
                    'polarity' => 0,
                    'weight' => 90,
                    'text' => $text,
                    'provenance' => self::PROVENANCE,
                    'source' => self::SOURCE,
                    'conditions' => null,
                ];
            }
        }

        return $rows;
    }

    /** Grahas for which significations have been supplied. */
    public static function covered(): array
    {
        return array_keys(self::map());
    }

    private static function map(): array
    {
        return [
            'Sun' => [
                'body' => 'the heart, the bones, the eyes, the head and skull, the bile (pitta), and the body\'s immunity',
                'relations' => 'the father, the king or ruler, your employer, and the soul itself',
                'qualities' => 'confidence, leadership, ego, ambition, kindness, discipline and personal aura',
                'other' => 'government service, rights and authority held in office, gold, and wheat',
            ],
            'Moon' => [
                'body' => 'the mind and brain, the chest and lungs, the left eye in a male chart, the blood, and the fluids of the body',
                'relations' => 'the mother, and the mother-in-law',
                'qualities' => 'changeability and restlessness, sensitivity, kindness, imagination, motherly care, sleep, and the steadiness or unsteadiness of mental peace',
                'other' => 'water, and whatever in life is subject to tides and phases',
            ],
            'Mars' => [
                'body' => 'the muscles, the bone marrow, the red blood cells, the liver, and the reproductive organs',
                'relations' => 'brothers and sisters, and those who serve in the military or the police',
                'qualities' => 'valour and courage, boldness, anger, and raw energy',
                'other' => 'land and property, weapons and sharp implements, and the fire element',
            ],
        ];
    }
}
