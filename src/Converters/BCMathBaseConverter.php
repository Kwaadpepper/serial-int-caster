<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial\Converters;

class BCMathBaseConverter implements BaseConverter
{
    /**
     * Converts a number from a base to another using a character set.
     *
     * @param string $numberInput The number to convert.
     * @param array  $fromBase    The character set for the source base.
     * @param array  $toBase      The character set for the destination base.
     * @return string The converted number.
     * @throws \Error If the BCMath extension is not loaded.
     */
    public function convert(string $numberInput, array $fromBase, array $toBase): string
    {
        if (!extension_loaded('bcmath')) {
            throw new \Error('The bcmath extension is required.');
        }

        // Use the count of the arrays to determine the base.
        $fromBaseLength = (string)count($fromBase);
        $toBaseLength   = (string)count($toBase);
        $numberChars    = str_split($numberInput);

        // Create a lookup table for the source base characters.
        $fromBaseMap = array_flip($fromBase);

        // 1: Convert the source base to base 10 (decimal) using BCMath
        $decimalValue = '0';
        foreach ($numberChars as $char) {
            // Use the lookup table for a quick search.
            if (!isset($fromBaseMap[$char])) {
                // Handle the case where the character is not found in the base.
                continue;
            }
            $pos          = $fromBaseMap[$char];
            $decimalValue = bcadd(bcmul($decimalValue, $fromBaseLength), (string)$pos);
        }

        // 2: Convert from base 10 to the destination base
        if (bccomp($decimalValue, '0') === 0) {
            return $toBase[0];
        }

        $result = '';
        while (bccomp($decimalValue, '0') > 0) {
            $remainder = bcmod($decimalValue, $toBaseLength);
            // Use the remainder to find the corresponding character in the destination base.
            $result       = $toBase[(int)$remainder] . $result;
            $decimalValue = bcdiv($decimalValue, $toBaseLength, 0);
        }

        return $result;
    }
}
