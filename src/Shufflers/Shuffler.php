<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial\Shufflers;

interface Shuffler
{
    /**
     * Shuffle a string
     *
     * @param string $string
     * @return void
     */
    public function shuffle(string &$string): void;

    /**
     * Unshuffle a string
     *
     * @param string $string
     * @return void
     */
    public function unshuffle(string &$string): void;

    /**
     * Get the actual seed.
     *
     * @return integer
     */
    public function seed(): int;
}
