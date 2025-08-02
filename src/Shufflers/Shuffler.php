<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial\Shufflers;

interface Shuffler
{
    /**
     * Shuffle a string with a given seed.
     *
     * @param string  $string The string to shuffle.
     * @param integer $seed   The seed for the shuffle algorithm.
     * @return void
     */
    public function shuffle(string &$string, int $seed): void;

    /**
     * Unshuffle a string with a given seed.
     *
     * @param string  $string The string to unshuffle.
     * @param integer $seed   The seed for the unshuffle algorithm.
     * @return void
     */
    public function unshuffle(string &$string, int $seed): void;
}
