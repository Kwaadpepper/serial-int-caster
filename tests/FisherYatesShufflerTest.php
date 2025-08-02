<?php

namespace Tests;

use Kwaadpepper\Serial\Shufflers\FisherYatesShuffler;
use PHPUnit\Framework\TestCase;

class FisherYatesShufflerTest extends TestCase
{
    /**
     * Test can shuffle string
     *
     * @return void
     */
    public function testShuffle()
    {
        $shuffler = new FisherYatesShuffler(1492);
        $string   = 'I love donuts';
        for ($i = 99; $i > 0; $i--) {
            $shuffler->shuffle($string);
        }
        $this->assertTrue(true);
    }

    /**
     * Test can unshuffle string
     *
     * @return void
     */
    public function testUnshuffle()
    {
        $shuffler = new FisherYatesShuffler(1492);
        $string   = 'uv otsId lnoe';
        for ($i = 99; $i > 0; $i--) {
            $shuffler->unshuffle($string);
        }
        $this->assertTrue(true);
    }

    /**
     * Test can retireve shuffled string
     *
     * @return void
     */
    public function testRetieveUnshuffle()
    {
        for ($i = 999; $i > 0; $i--) {
            $oldString = $this->generateRandomString();
            $string    = $oldString;
            $shuffler  = new FisherYatesShuffler(mt_rand(0, 9999999));
            $shuffler->shuffle($string);
            $shuffler->unshuffle($string);
            $this->assertEquals($oldString, $string);
        }
    }

    /**
     * Generates a random string
     * @param integer $length
     * @return string
     * @url https://stackoverflow.com/questions/4356289/php-random-string-generator
     */
    private function generateRandomString(int $length = 10): string
    {
        $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString     = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
