<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial\Converters;

class GmpBaseConverter implements BaseConverter
{
    /**
     * Converts a number from a base to another using a character set.
     *
     * @param string $numberInput   The number to convert.
     * @param string $fromBaseInput The character set for the source base.
     * @param string $toBaseInput   The character set for the destination base.
     * @return string The converted number.
     * @throws \Error If the GMP extension is not loaded.
     */
    public function convert(string $numberInput, string $fromBaseInput, string $toBaseInput): string
    {
        if (!extension_loaded('gmp')) {
            throw new \Error('L\'extension GMP est requise.');
        }

        $fromBase      = strlen($fromBaseInput);
        $toBase        = strlen($toBaseInput);
        $numberChars   = str_split($numberInput);
        $fromBaseChars = str_split($fromBaseInput);
        $toBaseChars   = str_split($toBaseInput);

        // 1: Convert the source base to base 10 (decimal) using GMP
        $decimalValueGmp = gmp_init(0);
        foreach ($numberChars as $char) {
            $pos             = array_search($char, $fromBaseChars, true);
            $decimalValueGmp = gmp_add(gmp_mul($decimalValueGmp, $fromBase), $pos);
        }

        // 2: Convert from base 10 to the destination base
        if (gmp_cmp($decimalValueGmp, 0) === 0) {
            return $toBaseChars[0];
        }

        $result = '';
        while (gmp_cmp($decimalValueGmp, 0) > 0) {
            // Gmp_div_qr divides the number and returns both quotient and remainder.
            // $qr[0] = quotient, $qr[1] = remainder.
            $qr              = gmp_div_qr($decimalValueGmp, $toBase);
            $remainder       = gmp_intval($qr[1]);
            $result          = $toBaseChars[$remainder] . $result;
            $decimalValueGmp = $qr[0];
        }

        return $result;
    }
}
