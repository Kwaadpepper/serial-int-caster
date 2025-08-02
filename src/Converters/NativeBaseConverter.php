<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial\Converters;

class NativeBaseConverter implements BaseConverter
{
    /**
     * @var string La valeur maximale que PHP_INT_MAX peut prendre sous forme de chaîne.
     */
    private const PHP_INT_MAX_STRING = '9223372036854775807';

    /**
     * Base 10 bytes
     */
    private const BASE10 = '0123456789';

    /**
     * Converts a number from a base to another using a character set.
     *
     * @param string $numberInput   The number to convert.
     * @param string $fromBaseInput The character set for the source base.
     * @param string $toBaseInput   The character set for the destination base.
     * @return string The converted number.
     * @throws \Error If the base is greater than 10 or if the number exceeds PHP_INT_MAX.
     */
    public function convert(string $numberInput, string $fromBaseInput, string $toBaseInput): string
    {
        if (strlen($fromBaseInput) > 10) {
            throw new \Error(
                'NativeConverter ne supporte pas la conversion à partir de bases supérieures à 10,
                il faut utiliser l\'extension bcmath ou gmp.'
            );
        }

        if ($fromBaseInput === self::BASE10) {
            $numberLen = strlen($numberInput);
            $maxLen    = strlen(self::PHP_INT_MAX_STRING);

            if ($numberLen > $maxLen || ($numberLen === $maxLen && $numberInput > self::PHP_INT_MAX_STRING)) {
                throw new \Error(
                    'NativeConverter ne peut pas gérer ce nombre car il dépasse la capacité des entiers de PHP,
                    il faut utiliser l\'extension bcmath ou gmp.'
                );
            }
        }

        $fromBase  = $fromBaseInput;
        $toBase    = $toBaseInput;
        $fromLen   = strlen($fromBase);
        $toLen     = strlen($toBase);
        $numberLen = strlen($numberInput);
        $retVal    = '';

        if ($fromLen == $toLen && $fromBase === $toBase) {
            return $numberInput;
        }

        $decimalValue = 0;
        $number       = (string)$numberInput;

        for ($i = 0; $i < $numberLen; $i++) {
            $char         = $number[$i];
            $charValue    = strpos($fromBase, $char);
            $decimalValue = $decimalValue * $fromLen + $charValue;
        }

        while ($decimalValue > 0) {
            $retVal       = $toBase[$decimalValue % $toLen] . $retVal;
            $decimalValue = (int)($decimalValue / $toLen);
        }

        return $retVal ?: '0';
    }
}
