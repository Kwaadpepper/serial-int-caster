<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial\Shufflers;

use mersenne_twister\twister;

/**
 * Fisher Yates Seeded shuffle using improved algorithm
 *
 * @author Jérémy Munsch <github@jeremydev.ovh>
 * @url https://stackoverflow.com/questions/24262147/can-a-seeded-shuffle-be-reversed
 * @url https://fr.wikipedia.org/wiki/M%C3%A9lange_de_Fisher-Yates
 */
final class FisherYatesShuffler implements Shuffler
{
    /** @var \mersenne_twister\twister */
    private $twister;

    /**
     * FisherYatesShuffler constructor.
     */
    public function __construct()
    {
        $this->twister = new twister();
    }

    /**
     * Shuffle a string with a given seed.
     *
     * @param string  $string The string to shuffle.
     * @param integer $seed   The seed for the shuffle algorithm.
     * @return void
     */
    public function shuffle(string &$string, int $seed): void
    {
        $this->twister->init_with_integer($seed);
        $length = strlen($string);
        for ($i = $length - 1; $i >= 1; $i--) {
            $j          = $this->random(0, $i);
            $t          = $string[$j];
            $string[$j] = $string[$i];
            $string[$i] = $t;
        }
    }

    /**
     * Unshuffle a string with a given seed.
     *
     * @param string  $string The string to unshuffle.
     * @param integer $seed   The seed for the unshuffle algorithm.
     * @return void
     */
    public function unshuffle(string &$string, int $seed): void
    {
        $this->twister->init_with_integer($seed);
        $length  = strlen($string);
        $indices = [];
        for ($i = $length - 1; $i >= 1; $i--) {
            $indices[$i] = $this->random(0, $i);
        }

        $indices = array_reverse($indices, true);
        foreach ($indices as $i => $j) {
            $t          = $string[$j];
            $string[$j] = $string[$i];
            $string[$i] = $t;
        }
    }

    /**
     * Generate a random number like Math.random between bounds
     *
     * @param integer $min
     * @param integer $max
     * @return integer
     */
    private function random(int $min, int $max): int
    {
        return $min + (int)($this->rand() * (($max - $min) + 1));
    }

    /**
     * Equivalent to Math.random using MersenneTwister int32
     *
     * @return float
     */
    private function rand(): float
    {
        return $this->twister->int32() / 0xFFFFFFFF;
    }
}
