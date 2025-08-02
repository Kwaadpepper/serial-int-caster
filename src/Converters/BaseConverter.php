<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial\Converters;

interface BaseConverter
{
    /**
     * Converts a number from a base to another using a character set.
     *
     * @param string $numberInput   The number to convert.
     * @param string $fromBaseInput The character set for the source base.
     * @param string $toBaseInput   The character set for the destination base.
     * @return string The converted number.
     */
    public function convert(string $numberInput, string $fromBaseInput, string $toBaseInput): string;
}
