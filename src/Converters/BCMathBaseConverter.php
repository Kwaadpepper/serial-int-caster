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
        $fromBase  = str_split($fromBaseInput, 1);
        $toBase    = str_split($toBaseInput, 1);
        $number    = str_split($numberInput, 1);
        $fromLen   = count($fromBase);
        $toLen     = count($toBase);
        $numberLen = count($number);
        $retVal    = '';

        if ($fromLen == $toLen) {
            return $numberInput;
        }

        $decimalValue = '0';
        for ($i = 1; $i <= $numberLen; $i++) {
            $decimalValue = bcadd($decimalValue, bcmul(
                (string)array_search($number[$i - 1], $fromBase),
                bcpow((string)$fromLen, (string)($numberLen - $i))
            ));
        }

        if ($toLen == 10) {
            return $decimalValue;
        }

        $retVal = '';
        while (bccomp($decimalValue, '0') > 0) {
            $retVal       = $toBase[bcmod($decimalValue, (string)$toLen)] . $retVal;
            $decimalValue = bcdiv($decimalValue, (string)$toLen, 0);
        }

        return $retVal == '' ? '0' : $retVal;
    }
}
