<?php

namespace App\Services\Astrology;

use App\Services\Astrology\Support\Zodiac;

/**
 * Renders the birth chart as inline SVG in both traditional styles.
 *
 * North Indian (default): the diamond. Houses are FIXED in position —
 * house 1 is always the top-centre triangle — and the signs rotate to
 * match the Lagna. Each cell shows its sign number.
 *
 * South Indian: a 4x4 grid where the SIGNS are fixed and the Lagna is
 * marked. Reading direction is clockwise from whichever cell holds the
 * ascendant.
 *
 * SVG is emitted inline (no external assets) so it renders in sandboxed
 * previews and prints cleanly.
 */
class ChartRenderer
{
    /** Abbreviations used inside the small chart cells. */
    private const ABBR = [
        'Sun' => 'Su', 'Moon' => 'Mo', 'Mars' => 'Ma', 'Mercury' => 'Me',
        'Jupiter' => 'Ju', 'Venus' => 'Ve', 'Saturn' => 'Sa',
        'Rahu' => 'Ra', 'Ketu' => 'Ke',
    ];

    /**
     * Label anchor points for the North Indian diamond on a 400x400 canvas.
     * House 1 top-centre, proceeding anticlockwise as tradition requires.
     */
    private const NORTH_POSITIONS = [
        1 => [200, 88],   2 => [105, 45],   3 => [52, 98],
        4 => [140, 200],  5 => [52, 302],   6 => [105, 355],
        7 => [200, 318],  8 => [295, 355],  9 => [348, 302],
        10 => [260, 200], 11 => [348, 98],  12 => [295, 45],
    ];

    /**
     * South Indian fixed-sign grid. Key = sign index, value = [col, row].
     */
    private const SOUTH_GRID = [
        11 => [0, 0], 0 => [1, 0], 1 => [2, 0], 2 => [3, 0],
        10 => [0, 1], 3 => [3, 1],
        9 => [0, 2], 4 => [3, 2],
        8 => [0, 3], 7 => [1, 3], 6 => [2, 3], 5 => [3, 3],
    ];

    public function render(array $facts, string $style = 'north', string $variant = 'D1'): string
    {
        $placements = $this->placements($facts, $variant);

        return $style === 'south'
            ? $this->renderSouth($facts, $placements)
            : $this->renderNorth($facts, $placements);
    }

    /**
     * Group planets by the house they occupy, with retrograde marks.
     *
     * @return array<int, array<int, string>> house number => labels
     */
    private function placements(array $facts, string $variant): array
    {
        $byHouse = array_fill(1, 12, []);
        $lagnaSign = $facts['lagna']['sign'];

        foreach ($facts['planets'] as $name => $planet) {
            if ($variant === 'D9') {
                $sign = $facts['divisional']['D9'][$name]['sign'];
                $house = Zodiac::houseOfSign($sign, $lagnaSign);
            } else {
                $house = $planet['house'];
            }

            $label = self::ABBR[$name] ?? substr($name, 0, 2);

            if ($planet['retrograde'] && ! in_array($name, ['Rahu', 'Ketu'], true)) {
                $label .= '℞';
            }

            if ($planet['combust'] ?? false) {
                $label .= '*';
            }

            $byHouse[$house][] = $label;
        }

        return $byHouse;
    }

    private function renderNorth(array $facts, array $placements): string
    {
        $lagnaSign = $facts['lagna']['sign'];

        $svg = [];
        $svg[] = '<svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:auto;max-width:440px;font-family:Georgia,serif;">';
        $svg[] = '<rect x="0" y="0" width="400" height="400" fill="#fffdf8" stroke="#4a2c5a" stroke-width="2.5"/>';

        // Both diagonals
        $svg[] = '<line x1="0" y1="0" x2="400" y2="400" stroke="#4a2c5a" stroke-width="1.5"/>';
        $svg[] = '<line x1="400" y1="0" x2="0" y2="400" stroke="#4a2c5a" stroke-width="1.5"/>';

        // Inner diamond connecting the midpoints of each edge
        $svg[] = '<polygon points="200,0 400,200 200,400 0,200" fill="none" stroke="#4a2c5a" stroke-width="1.5"/>';

        for ($house = 1; $house <= 12; $house++) {
            [$x, $y] = self::NORTH_POSITIONS[$house];
            $sign = Zodiac::signOfHouse($house, $lagnaSign);

            // Sign number in the corner of the cell
            $svg[] = sprintf(
                '<text x="%d" y="%d" text-anchor="middle" font-size="11" fill="#b5643f" font-weight="bold">%d</text>',
                $x, $y - 14, $sign + 1
            );

            // Mark the Lagna cell
            if ($house === 1) {
                $svg[] = sprintf(
                    '<text x="%d" y="%d" text-anchor="middle" font-size="9" fill="#7b3f61">Lagna</text>',
                    $x, $y - 25
                );
            }

            $planets = $placements[$house];
            $lineHeight = 13;

            foreach (array_chunk($planets, 3) as $rowIndex => $row) {
                $svg[] = sprintf(
                    '<text x="%d" y="%d" text-anchor="middle" font-size="12" fill="#2b2520">%s</text>',
                    $x, $y + ($rowIndex * $lineHeight), e(implode(' ', $row))
                );
            }
        }

        $svg[] = '</svg>';

        return implode("\n", $svg);
    }

    private function renderSouth(array $facts, array $placements): string
    {
        $lagnaSign = $facts['lagna']['sign'];

        $cell = 100;
        $svg = [];
        $svg[] = '<svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:auto;max-width:440px;font-family:Georgia,serif;">';
        $svg[] = '<rect x="0" y="0" width="400" height="400" fill="#fffdf8" stroke="#4a2c5a" stroke-width="2.5"/>';

        // The hollow centre of a South Indian chart
        $svg[] = '<rect x="100" y="100" width="200" height="200" fill="#f4f1ea" stroke="#4a2c5a" stroke-width="1.5"/>';

        foreach (self::SOUTH_GRID as $sign => [$col, $row]) {
            $x = $col * $cell;
            $y = $row * $cell;
            $house = Zodiac::houseOfSign($sign, $lagnaSign);
            $isLagna = $sign === $lagnaSign;

            $svg[] = sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" fill="%s" stroke="#4a2c5a" stroke-width="1.5"/>',
                $x, $y, $cell, $cell, $isLagna ? '#f6eef2' : '#fffdf8'
            );

            // Sign abbreviation, top-left of the cell
            $svg[] = sprintf(
                '<text x="%d" y="%d" font-size="10" fill="#b5643f" font-weight="bold">%s</text>',
                $x + 6, $y + 15, e(substr(Zodiac::SIGNS[$sign], 0, 3))
            );

            // House number, top-right
            $svg[] = sprintf(
                '<text x="%d" y="%d" text-anchor="end" font-size="10" fill="#9a9186">%d</text>',
                $x + $cell - 6, $y + 15, $house
            );

            if ($isLagna) {
                // Diagonal slash marking the ascendant, by convention
                $svg[] = sprintf(
                    '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#7b3f61" stroke-width="1.5"/>',
                    $x, $y, $x + 28, $y + 28
                );
            }

            $planets = $placements[$house];
            foreach (array_chunk($planets, 2) as $rowIndex => $chunk) {
                $svg[] = sprintf(
                    '<text x="%d" y="%d" text-anchor="middle" font-size="12" fill="#2b2520">%s</text>',
                    $x + ($cell / 2), $y + 45 + ($rowIndex * 14), e(implode(' ', $chunk))
                );
            }
        }

        // Centre label
        $svg[] = sprintf(
            '<text x="200" y="195" text-anchor="middle" font-size="13" fill="#7b3f61">%s Lagna</text>',
            e(Zodiac::SIGNS[$lagnaSign])
        );
        $svg[] = sprintf(
            '<text x="200" y="215" text-anchor="middle" font-size="11" fill="#9a9186">%s</text>',
            e($facts['lagna']['degree_formatted'])
        );

        $svg[] = '</svg>';

        return implode("\n", $svg);
    }
}
