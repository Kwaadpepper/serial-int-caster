<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial;

use Kwaadpepper\Serial\Converters\BaseConverter;
use Kwaadpepper\Serial\Exceptions\SerialCasterException;

/**
 * Encode Integers to a Serial String and decode them
 *
 * Note: Does not support encoding the value 0
 * @author Jérémy Munsch <github@jeremydev.ovh>
 */
final class SerialCaster
{
    /**
     * Base 10 bytes
     */
    private const BASE10 = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /** @var \Kwaadpepper\Serial\FisherYatesShuffler */
    private $shuffler;

    /** @var \Kwaadpepper\Serial\Converters\BaseConverter Le moteur de conversion de base. */
    private $converter;

    /**
     * Available chars for Serial generation
     *
     * @var array<int, string>
     */
    private $chars = [];

    /**
     * SerialCaster constructor.
     *
     * @param \Kwaadpepper\Serial\Converters\BaseConverter $converter
     * @param string|null                                  $chars
     * @throws \Kwaadpepper\Serial\Exceptions\SerialCasterException If the chars are not valid.
     */
    public function __construct(BaseConverter $converter, ?string $chars = null)
    {
        $this->converter = $converter;
        $this->setChars($chars);
    }

    /**
     * Encode an integer using chars generating a serial of a minimum length
     *
     * @param integer $number The number to encode in the serial string.
     * @param integer $seed   The seed used to suffle the serial bytes order.
     * @param integer $length The serial desired length.
     * @return string The serial
     * @throws \Kwaadpepper\Serial\Exceptions\SerialCasterException If a config error happens.
     */
    public function encode(int $number, int $seed = 0, int $length = 6): string
    {
        $this->init($number, $length);
        $charsCount = str_pad((string)count($this->chars), 2, '0', \STR_PAD_LEFT);
        $outString  = (string)$number . $charsCount;
        $outString  = str_pad(
            $this->convBase($outString, self::BASE10, $this->chars),
            $length,
            $this->chars[0],
            \STR_PAD_LEFT
        );
        $this->shuffle($seed, $outString);
        return $outString;
    }

    /**
     * Decode an integer using chars
     *
     * @param string  $serial The serial ton decode.
     * @param integer $seed   The seed used to randomize the serial.
     * @return integer The number encoded in the serial
     * @throws SerialCasterException If a wrong serial or charlist is given.
     */
    public function decode(string $serial, int $seed = 0): int
    {
        $this->unshuffle($seed, $serial);
        $serialLength = strlen($serial);

        for ($i = 0; $i < $serialLength; $i++) {
            if (!in_array($serial[$i], $this->chars, true)) {
                throw new SerialCasterException(sprintf(
                    '%s::decode un caractère non valide `%s` est présent',
                    __CLASS__,
                    $serial[$i]
                ));
            }
        }

        $outNumber = $this->convBase($serial, $this->chars, self::BASE10);

        if (strlen($outNumber) < 3) {
            throw new SerialCasterException(sprintf('%s::decode un code série invalide à été donné', __CLASS__));
        }

        $charsCount = (int)substr($outNumber, -2);
        $outNumber  = (int)substr($outNumber, 0, strlen($outNumber) - 2);

        if ($charsCount !== count($this->chars)) {
            throw new SerialCasterException(sprintf(
                '%s::decode la liste de caractère pour décoder ne semble
                pas correspondre à celui utilisé pour l\'encodage',
                __CLASS__
            ));
        }
        return $outNumber;
    }

    /**
     * Initialize SerialCaster
     *
     * @param integer $number
     * @param integer $length
     * @return void
     * @throws \Kwaadpepper\Serial\Exceptions\SerialCasterException If parameters are wrong.
     */
    private function init(int $number, int $length): void
    {
        if ($length <= 0) {
            throw new SerialCasterException(sprintf('%s need a length of minimum 1', __CLASS__));
        }
        if (count($this->chars) < 2) {
            throw new SerialCasterException(sprintf('%s need a minimum length of 2 unique chars', __CLASS__));
        }
        if (count($this->chars) > 99) {
            throw new SerialCasterException(sprintf('%s can have a minimum length of 99 unique chars', __CLASS__));
        }
        $minimumLength = $this->calculateNewBaseLengthFromBase10($number, count($this->chars)) + 2;
        if ($length < $minimumLength) {
            throw new SerialCasterException(sprintf(
                '%s need a minimum length of %d',
                __CLASS__,
                $minimumLength
            ));
        }
    }

    /**
     * If seed is different than 0, then shuffles the serial bytes
     * using the seed
     *
     * @param integer $seed
     * @param string  $serial
     * @return void
     */
    private function shuffle(int $seed, string &$serial): void
    {
        if ($seed) {
            $this->setupShuffle($seed);
            $this->shuffler->shuffle($serial);
            $this->rotateLeft($serial, $this->sumString($serial) % strlen($serial));
        }
    }

    /**
     * If seed is different than 0, then unshuffles the serial bytes
     * using the seed
     *
     * @param integer $seed
     * @param string  $serial
     * @return void
     */
    private function unshuffle(int $seed, string &$serial): void
    {
        if ($seed) {
            $this->setupShuffle($seed);
            $this->rotateRight($serial, $this->sumString($serial) % strlen($serial));
            $this->shuffler->unshuffle($serial);
        }
    }

    /**
     * Setup shuffle
     *
     * @param integer $seed
     * @return void
     */
    private function setupShuffle(int $seed): void
    {
        if (!$this->shuffler || $this->shuffler->seed() !== $seed) {
            $this->shuffler = new FisherYatesShuffler($seed);
        }
    }

    /**
     * Setup char dict
     *
     * @param string|null $chars
     * @return void
     */
    private function setChars(?string $chars = null): void
    {
        if ($chars === null) {
            $this->setupDefaultChars();
            return;
        }

        $uniqueChars = count_chars($chars, 3);
        $this->chars = str_split($uniqueChars);
    }

    /**
     * Setup default chars
     *
     * @return void
     */
    private function setupDefaultChars(): void
    {
        $defaultChars = array_merge(
            range('a', 'z'),
            range('A', 'Z'),
            range('0', '9')
        );

        $this->chars = str_split(count_chars(implode($defaultChars), 3));
    }

    /**
     * Converts any number from a base to another using chars
     *
     * @param string $numberInput
     * @param array  $fromBase
     * @param array  $toBase
     * @return string
     * @url https://www.php.net/manual/fr/function.base-convert.php#106546
     */
    private function convBase(string $numberInput, array $fromBase, array $toBase): string
    {
        return $this->converter->convert(
            $numberInput,
            $fromBase,
            $toBase
        );
    }

    /**
     * Calculate the length of a decimal number in a new base length
     *
     * @param integer $number
     * @param integer $base
     * @return integer
     */
    private function calculateNewBaseLengthFromBase10(int $number, int $base): int
    {
        if ($number <= 0) {
            return 1;
        }
        return (int)(floor(log($number, $base)) + 1);
    }

    /**
     * Move chars in a string from left to right
     *
     * @param string  $string
     * @param integer $distance
     * @return void
     */
    private function rotateLeft(string &$string, int $distance): void
    {
        $string = substr($string, $distance) .
            substr($string, 0, $distance);
    }

    /**
     * Move chars in a string from right to left
     *
     * @param string  $string
     * @param integer $distance
     * @return void
     */
    private function rotateRight(string &$string, int $distance): void
    {
        $length    = strlen($string);
        $distance %= $length;
        $string    = substr($string, $length - $distance) .
            substr($string, 0, $length - $distance);
    }

    /**
     * Sum all string chars values
     *
     * @param string $string
     * @return integer
     */
    private function sumString(string $string): int
    {
        return array_sum(array_map('ord', str_split($string)));
    }
}
