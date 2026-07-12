<?php

namespace App\Services;

class BarcodeService
{
    private const CODE39 = [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
        '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
        '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
        'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
        'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
        'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
        'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
        'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
        'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
        '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
        '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
    ];

    public function normalize(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/[^A-Z0-9 .\-\/+$%]/', '-', $value) ?? '';

        return $value !== '' ? $value : 'EMPTY';
    }

    public function svg(string $value): string
    {
        $value = '*' . $this->normalize($value) . '*';
        $narrow = 2;
        $wide = 5;
        $height = 86;
        $quiet = 14;
        $x = $quiet;
        $bars = '';

        foreach (str_split($value) as $char) {
            $pattern = self::CODE39[$char] ?? self::CODE39['-'];
            foreach (str_split($pattern) as $index => $widthKey) {
                $width = $widthKey === 'w' ? $wide : $narrow;
                if ($index % 2 === 0) {
                    $bars .= '<rect x="' . $x . '" y="10" width="' . $width . '" height="' . $height . '" fill="#111"/>';
                }
                $x += $width;
            }
            $x += $narrow;
        }

        $width = $x + $quiet;
        $label = htmlspecialchars(trim($value, '*'), ENT_QUOTES, 'UTF-8');

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="124" viewBox="0 0 ' . $width . ' 124" role="img" aria-label="Barcode ' . $label . '">'
            . '<rect width="100%" height="100%" fill="#fff"/>'
            . $bars
            . '<text x="' . ($width / 2) . '" y="116" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" fill="#111">' . $label . '</text>'
            . '</svg>';
    }
}
