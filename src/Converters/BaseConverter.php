<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial\Converters;

interface BaseConverter
{
    /**
     * Converts a number from a base to another using a character set.
     *
     * @param string $numberInput The number to convert.
     * @param array  $fromBase    The character set for the source base.
     * @param array  $toBase      The character set for the destination base.
     * @return string The converted number.
     */
    public function convert(string $numberInput, array $fromBase, array $toBase): string;
}
