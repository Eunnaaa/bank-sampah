<?php

namespace App\Services;

class QrCodeService
{
    private static array $exp = [];

    private static array $log = [];

    private static bool $initialized = false;

    private static function initGf(): void
    {
        if (self::$initialized) {
            return;
        }

        $val = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $val;
            self::$log[$val] = $i;
            $val <<= 1;
            if ($val & 0x100) {
                $val ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$exp[$i] = self::$exp[$i - 255];
        }
        self::$initialized = true;
    }

    private static function gfMul(int $x, int $y): int
    {
        if ($x === 0 || $y === 0) {
            return 0;
        }

        return self::$exp[self::$log[$x] + self::$log[$y]];
    }

    /**
     * @return array<int, int>
     */
    private static function getRsGeneratorPoly(int $degree): array
    {
        $poly = [1];
        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($poly) + 1, 0);
            $factor = self::$exp[$i];
            for ($j = 0; $j < count($poly); $j++) {
                $next[$j] ^= $poly[$j];
                $next[$j + 1] ^= self::gfMul($poly[$j], $factor);
            }
            $poly = $next;
        }

        return $poly;
    }

    /**
     * @param  array<int, int>  $data
     * @return array<int, int>
     */
    private static function computeRs(array $data, int $ecCount): array
    {
        self::initGf();
        $gen = self::getRsGeneratorPoly($ecCount);
        $remainder = array_fill(0, $ecCount, 0);

        foreach ($data as $b) {
            $factor = $b ^ $remainder[0];
            array_shift($remainder);
            $remainder[] = 0;
            if ($factor !== 0) {
                for ($i = 0; $i < $ecCount; $i++) {
                    $remainder[$i] ^= self::gfMul($gen[$i + 1], $factor);
                }
            }
        }

        return $remainder;
    }

    /**
     * Encode text into standard QR Code Version 3 Level M matrix (29x29)
     *
     * @return array<int, array<int, int>>
     */
    public static function encodeMatrix(string $text): array
    {
        self::initGf();
        $len = strlen($text);
        if ($len > 42) {
            $text = substr($text, 0, 42);
            $len = 42;
        }

        $bits = '0100'; // Byte mode
        $bits .= str_pad(decbin($len), 8, '0', STR_PAD_LEFT);
        for ($i = 0; $i < $len; $i++) {
            $bits .= str_pad(decbin(ord($text[$i])), 8, '0', STR_PAD_LEFT);
        }
        $bits .= '0000'; // Terminator

        $rem = strlen($bits) % 8;
        if ($rem !== 0) {
            $bits .= str_repeat('0', 8 - $rem);
        }

        $dataCodewords = [];
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $dataCodewords[] = bindec(substr($bits, $i, 8));
        }

        $padBytes = [0xEC, 0x11];
        $padIdx = 0;
        while (count($dataCodewords) < 44) {
            $dataCodewords[] = $padBytes[$padIdx];
            $padIdx = ($padIdx + 1) % 2;
        }

        $ecCodewords = self::computeRs($dataCodewords, 26);
        $allCodewords = array_merge($dataCodewords, $ecCodewords);

        $size = 29;
        $matrix = array_fill(0, $size, array_fill(0, $size, null));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        // 1. Finder patterns (7x7) + separators
        $addFinder = function (int $top, int $left) use (&$matrix, &$reserved, $size) {
            for ($r = -1; $r <= 7; $r++) {
                for ($c = -1; $c <= 7; $c++) {
                    $rr = $top + $r;
                    $cc = $left + $c;
                    if ($rr >= 0 && $rr < $size && $cc >= 0 && $cc < $size) {
                        $reserved[$rr][$cc] = true;
                        if ($r >= 0 && $r <= 6 && $c >= 0 && $c <= 6) {
                            $matrix[$rr][$cc] = ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) ? 1 : 0;
                        } else {
                            $matrix[$rr][$cc] = 0;
                        }
                    }
                }
            }
        };

        $addFinder(0, 0);
        $addFinder(0, $size - 7);
        $addFinder($size - 7, 0);

        // 2. Alignment pattern at (22, 22)
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $reserved[22 + $r][22 + $c] = true;
                $matrix[22 + $r][22 + $c] = (abs($r) === 2 || abs($c) === 2 || ($r === 0 && $c === 0)) ? 1 : 0;
            }
        }

        // 3. Timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            if (! $reserved[6][$i]) {
                $reserved[6][$i] = true;
                $matrix[6][$i] = ($i % 2 === 0) ? 1 : 0;
            }
            if (! $reserved[$i][6]) {
                $reserved[$i][6] = true;
                $matrix[$i][6] = ($i % 2 === 0) ? 1 : 0;
            }
        }

        // Dark module
        $reserved[4 * 3 + 9][8] = true;
        $matrix[4 * 3 + 9][8] = 1;

        // Reserve Format info
        for ($i = 0; $i < 9; $i++) {
            if ($i !== 6) {
                $reserved[8][$i] = $reserved[$i][8] = true;
            }
        }
        for ($i = $size - 8; $i < $size; $i++) {
            $reserved[8][$i] = true;
        }
        for ($i = $size - 7; $i < $size; $i++) {
            $reserved[$i][8] = true;
        }

        // Convert codewords to bit string
        $allBits = '';
        foreach ($allCodewords as $cw) {
            $allBits .= str_pad(decbin($cw), 8, '0', STR_PAD_LEFT);
        }

        // Zig-zag data placement
        $bitIdx = 0;
        $totalBits = strlen($allBits);
        $right = $size - 1;
        $dir = -1;
        while ($right > 0) {
            if ($right === 6) {
                $right--;
            }
            $colA = $right;
            $colB = $right - 1;
            $rows = ($dir === -1) ? range($size - 1, 0, -1) : range(0, $size - 1);
            foreach ($rows as $r) {
                foreach ([$colA, $colB] as $c) {
                    if (! $reserved[$r][$c]) {
                        $matrix[$r][$c] = ($bitIdx < $totalBits) ? (int) $allBits[$bitIdx++] : 0;
                    }
                }
            }
            $dir = -$dir;
            $right -= 2;
        }

        // Mask 0: (row + col) % 2 === 0
        $formatBits = '101010000010010';
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if (! $reserved[$r][$c]) {
                    if (($r + $c) % 2 === 0) {
                        $matrix[$r][$c] ^= 1;
                    }
                }
            }
        }

        // Write format bits
        $hCols = [0, 1, 2, 3, 4, 5, 7, 8];
        for ($i = 0; $i < 8; $i++) {
            $matrix[8][$hCols[$i]] = (int) $formatBits[$i];
        }
        $vRows = [8, 7, 5, 4, 3, 2, 1, 0];
        for ($i = 8; $i < 15; $i++) {
            $matrix[$vRows[$i - 8]][8] = (int) $formatBits[$i];
        }
        for ($i = 0; $i < 8; $i++) {
            $matrix[$size - 1 - $i][8] = (int) $formatBits[$i];
        }
        for ($i = 8; $i < 15; $i++) {
            $matrix[8][$size - 15 + $i] = (int) $formatBits[$i];
        }

        return $matrix;
    }

    /**
     * Render QR Code to SVG string
     */
    public static function generateSvg(string $text, int $pixelSize = 6, string $fg = '#173f35', string $bg = '#ffffff'): string
    {
        $matrix = self::encodeMatrix($text);
        $size = count($matrix);
        $quiet = 3;
        $totalSize = ($size + 2 * $quiet) * $pixelSize;

        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 {$totalSize} {$totalSize}\" width=\"100%\" height=\"100%\" style=\"display: block;\">\n";
        $svg .= "    <rect width=\"{$totalSize}\" height=\"{$totalSize}\" fill=\"{$bg}\" rx=\"8\" />\n";
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($matrix[$r][$c] === 1) {
                    $x = ($c + $quiet) * $pixelSize;
                    $y = ($r + $quiet) * $pixelSize;
                    $svg .= "    <rect x=\"{$x}\" y=\"{$y}\" width=\"{$pixelSize}\" height=\"{$pixelSize}\" fill=\"{$fg}\" />\n";
                }
            }
        }
        $svg .= '</svg>';

        return $svg;
    }
}
