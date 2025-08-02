<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial\Converters;

class BCMathBaseConverter implements BaseConverter
{
    /**
     * Converts a number from a base to another using a character set.
     *
     * @param string $numberInput   The number to convert.
     * @param string $fromBaseInput The character set for the source base.
     * @param string $toBaseInput   The character set for the destination base.
     * @return string The converted number.
     * @throws \Error If the BCMath extension is not loaded.
     */
    public function convert(string $numberInput, string $fromBaseInput, string $toBaseInput): string
    {
        if (!extension_loaded('bcmath')) {
            throw new \Error('L\'extension bcmath est requise.');
        }

        $fromBase      = (string)strlen($fromBaseInput);
        $toBase        = (string)strlen($toBaseInput);
        $numberChars   = str_split($numberInput);
        $fromBaseChars = str_split($fromBaseInput);
        $toBaseChars   = str_split($toBaseInput);

        // 1: Convert the source base to base 10 (decimal) using BCMath
        $decimalValue = '0';
        foreach ($numberChars as $char) {
            $pos = array_search($char, $fromBaseChars, true);
            if ($pos === false) {
                 // Handle the case where the character is not found in the base.
                continue;
            }
            $decimalValue = bcadd(bcmul($decimalValue, $fromBase), (string)$pos);
        }

        // 2: Convert from base 10 to the destination base
        if (bccomp($decimalValue, '0') === 0) {
            return $toBaseChars[0];
        }

        $result = '';
        while (bccomp($decimalValue, '0') > 0) {
            $remainder = bcmod($decimalValue, $toBase);
            // Use the remainder to find the corresponding character in the destination base.
            $result       = $toBaseChars[(int)$remainder] . $result;
            $decimalValue = bcdiv($decimalValue, $toBase, 0);
        }

        return $result;
    }
}
