<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial\Converters;

class GmpBaseConverter implements BaseConverter
{
    /**
     * Converts a number from a base to another using a character set.
     *
     * @param string $numberInput The number to convert.
     * @param array  $fromBase    The character set for the source base.
     * @param array  $toBase      The character set for the destination base.
     * @return string The converted number.
     * @throws \Error If the GMP extension is not loaded.
     */
    public function convert(string $numberInput, array $fromBase, array $toBase): string
    {
        if (!extension_loaded('gmp')) {
            throw new \Error('The GMP extension is required.');
        }

        // Use the count of the arrays to determine the base.
        $fromBaseLength = count($fromBase);
        $toBaseLength   = count($toBase);
        $numberChars    = str_split($numberInput);

        // Create a lookup table for the source base characters.
        $fromBaseMap = array_flip($fromBase);

        // 1: Convert the source base to base 10 (decimal) using GMP.
        $decimalValueGmp = gmp_init(0);
        foreach ($numberChars as $char) {
            if (!isset($fromBaseMap[$char])) {
                // Handle the case where the character is not found in the base.
                continue;
            }
            $pos             = $fromBaseMap[$char];
            $decimalValueGmp = gmp_add(gmp_mul($decimalValueGmp, $fromBaseLength), $pos);
        }

        // 2: Convert from base 10 to the destination base.
        if (gmp_cmp($decimalValueGmp, 0) === 0) {
            return $toBase[0];
        }

        $result = '';
        while (gmp_cmp($decimalValueGmp, 0) > 0) {
            // Gmp_div_qr divides the number and returns both quotient and remainder.
            // $qr[0] = quotient, $qr[1] = remainder.
            $qr              = gmp_div_qr($decimalValueGmp, $toBaseLength);
            $remainder       = gmp_intval($qr[1]);
            $result          = $toBase[$remainder] . $result;
            $decimalValueGmp = $qr[0];
        }

        return $result;
    }
}
