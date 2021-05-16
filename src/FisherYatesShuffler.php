<?php

namespace Kwaadpepper\Serial;

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

    /** @var int $seed */
    private $seed;

    public function __construct(int $seed)
    {
        $this->seed = $seed;
    }

    public function shuffle(string &$string)
    {
        mt_srand($this->seed);
        $length = strlen($string);
        for ($i = $length - 1; $i >= 1; $i--) {
            $j = mt_rand(0, $i);
            $t = $string[$j];
            $string[$j] = $string[$i];
            $string[$i] = $t;
        }
    }

    public function unshuffle(string &$string)
    {
        mt_srand($this->seed);
        $length = strlen($string);
        $indices = [];
        for ($i = $length - 1; $i >= 1; $i--) {
            $indices[$i] = mt_rand(0, $i);
        }

        $indices = array_reverse($indices, true);
        foreach ($indices as $i => $j) {
            $t = $string[$j];
            $string[$j] = $string[$i];
            $string[$i] = $t;
        }
    }
}
