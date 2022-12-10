<?php

namespace Kwaadpepper\Serial;

use mersenne_twister\twister;

/**
 *
 * Fisher Yates Seeded shuffle using improved algorithm and seed
 *
 * @author Jérémy Munsch <github@jeremydev.ovh>
 * @url https://stackoverflow.com/questions/24262147/can-a-seeded-shuffle-be-reversed
 * @url https://fr.wikipedia.org/wiki/M%C3%A9lange_de_Fisher-Yates
 */
class FisherYatesShuffler
{
    /** @var \mersenne_twister\twister $twister */
    private $twister;

    /** @var string $seed */
    private $seed;

    /**
     * FisherYatesShuffler
     * @param integer $seed
     * @return void
     */
    public function __construct(int $seed)
    {
        $this->seed    = $seed;
        $this->twister = new twister();
    }

    /**
     * Shuffle a string
     *
     * @param string $string
     * @return void
     */
    public function shuffle(string &$string)
    {
        $this->twister->init_with_integer($this->seed);
        $length = strlen($string);
        for ($i = $length - 1; $i >= 1; $i--) {
            $j          = $this->random(0, $i);
            $t          = $string[$j];
            $string[$j] = $string[$i];
            $string[$i] = $t;
        }
    }

    /**
     * Unshuffle a string
     *
     * @param string $string
     * @return void
     */
    public function unshuffle(string &$string)
    {
        $this->twister->init_with_integer($this->seed);
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
