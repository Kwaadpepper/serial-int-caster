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

        if ($fromBaseInput === $toBaseInput) {
            return $numberInput;
        }

        $decimalNumber = gmp_init($numberInput, strlen($fromBaseInput));
        $result        = gmp_strval($decimalNumber, strlen($toBaseInput));

        return (string)$result;
    }
}
