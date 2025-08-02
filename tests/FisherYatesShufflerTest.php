<?php

namespace Tests;

use Kwaadpepper\Serial\Shufflers\FisherYatesShuffler;
use PHPUnit\Framework\TestCase;

class FisherYatesShufflerTest extends TestCase
{
    /** @var \Kwaadpepper\Serial\Shufflers\Shuffler */
    private $shuffler;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->shuffler = new FisherYatesShuffler();
    }
    /**
     * Test can shuffle string
     *
     * @return void
     */
    public function testShuffle()
    {
        // * GIVEN
        $seed           = 1492;
        $string         = 'I love donuts';
        $originalString = $string;

        // * WHEN
        $this->shuffler->shuffle($string, $seed);

        // * THEN
        $this->assertNotEquals($string, $originalString);
        $this->assertEquals(count_chars($originalString, 1), count_chars($string, 1));
    }

    /**
     * Test can unshuffle string
     *
     * @return void
     */
    public function testUnshuffle()
    {
        // * GIVEN
        $seed           = 1492;
        $shuffledString = 'uv otsId lnoe';
        $expectedString = 'I love donuts';

        // * WHEN
        $this->shuffler->unshuffle($shuffledString, $seed);

        // * THEN
        $this->assertEquals($expectedString, $shuffledString);
    }

    /**
     * Test can retrieve shuffled string
     *
     * @return void
     */
    public function testRetieveUnshuffle()
    {
        for ($i = 999; $i > 0; $i--) {
            // * GIVEN
            $seed      = mt_rand(0, 9999999);
            $oldString = $this->generateRandomString();
            $string    = $oldString;

            // * WHEN
            $this->shuffler->shuffle($string, $seed);
            $this->shuffler->unshuffle($string, $seed);

            // * THEN
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
