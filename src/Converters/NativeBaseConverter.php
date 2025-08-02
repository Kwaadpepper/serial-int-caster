<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial\Converters;

class NativeBaseConverter implements BaseConverter
{
    /**
     * @var string The maximum integer value in PHP as a string.
     */
    private const PHP_INT_MAX_STRING = '9223372036854775807';

    /**
     * Base 10 bytes
     */
    private const BASE10 = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /**
     * Converts a number from a base to another using a character set.
     *
     * @param string $numberInput The number to convert.
     * @param array  $fromBase    The character set for the source base.
     * @param array  $toBase      The character set for the destination base.
     * @return string The converted number.
     * @throws \Error If the base is greater than 10 or if the number exceeds PHP_INT_MAX.
     */
    public function convert(string $numberInput, array $fromBase, array $toBase): string
    {
        if (count($fromBase) > 10) {
            throw new \Error(
                'NativeConverter does not support conversion from bases greater than 10,
                please use the BCMath or GMP extension.'
            );
        }

        if ($fromBase === self::BASE10) {
            $numberLen = strlen($numberInput);
            $maxLen    = strlen(self::PHP_INT_MAX_STRING);

            if ($numberLen > $maxLen || ($numberLen === $maxLen && $numberInput > self::PHP_INT_MAX_STRING)) {
                throw new \Error(
                    'NativeConverter cannot handle this number as it exceeds the capacity of PHP integers,
                    please use the BCMath or GMP extension.'
                );
            }
        }

        $fromLen   = count($fromBase);
        $toLen     = count($toBase);
        $numberLen = strlen($numberInput);
        $retVal    = '';

        if ($fromLen === $toLen && $fromBase === $toBase) {
            return $numberInput;
        }

        $decimalValue = 0;
        $fromBaseMap  = array_flip($fromBase);
        $number       = (string)$numberInput;

        for ($i = 0; $i < $numberLen; $i++) {
            $char         = $number[$i];
            $charValue    = $fromBaseMap[$char];
            $decimalValue = $decimalValue * $fromLen + $charValue;
        }

        while ($decimalValue > 0) {
            $retVal       = $toBase[$decimalValue % $toLen] . $retVal;
            $decimalValue = (int)($decimalValue / $toLen);
        }

        return $retVal ?: $toBase[0];
    }
}
