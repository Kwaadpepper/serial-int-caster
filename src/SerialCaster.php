<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial;

use Kwaadpepper\Serial\Converters\BCMathBaseConverter;
use Kwaadpepper\Serial\Converters\GmpBaseConverter;
use Kwaadpepper\Serial\Converters\NativeBaseConverter;
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
    private const BASE10 = '0123456789';

    /** @var \Kwaadpepper\Serial\FisherYatesShuffler */
    private static $shuffler;

    /** @var \Kwaadpepper\Serial\Converters\BaseConverter Le moteur de conversion de base. */
    private static $converter;

    /**
     * Available chars for Serial generation
     *
     * @var string
     */
    private static $chars = '';

    /**
     * Encode an integer using chars generating a serial of a minimum length
     *
     * @param integer $number The number to encode in the serial string.
     * @param integer $seed   The seed used to suffle the serial bytes order.
     * @param integer $length The serial desired length.
     * @param string  $chars  The bytes used to generate the serial string.
     * @return string         The serial
     * @throws \Kwaadpepper\Serial\Exceptions\SerialCasterException If a config error happens.
     */
    public static function encode(int $number, int $seed = 0, int $length = 6, string $chars = ''): string
    {
        self::init($number, $length, $chars);
        $charsCount = str_pad((string)strlen(self::$chars), 2, '0', \STR_PAD_LEFT);
        $outString  = (string)$number . $charsCount;
        $outString  = str_pad(
            self::convBase($outString, self::BASE10, self::$chars),
            $length,
            self::$chars[0],
            \STR_PAD_LEFT
        );
        self::shuffle($seed, $outString);
        return $outString;
    }

    /**
     * Decode an integer using chars
     *
     * @param string  $serial The serial ton decode.
     * @param integer $seed   The seed used to randomize the serial.
     * @param string  $chars  The bytes used to generate the serial.
     * @return integer         The number encoded in the serial
     * @throws SerialCasterException If a wrong serial or charlist is given.
     */
    public static function decode(string $serial, int $seed = 0, string $chars = ''): int
    {
        self::setChars($chars);
        self::unshuffle($seed, $serial);
        $serialLength = strlen($serial);

        $charsMap = array_flip(str_split(self::$chars));
        for ($i = 0; $i < $serialLength; $i++) {
            if (!isset($charsMap[$serial[$i]])) {
                throw new SerialCasterException(sprintf(
                    '%s::decode un caractère non valide `%s` est présent',
                    __CLASS__,
                    $serial[$i]
                ));
            }
        }

        $outNumber = self::convBase($serial, self::$chars, self::BASE10);

        if (strlen($outNumber) < 3) {
            throw new SerialCasterException(sprintf('%s::decode un code série invalide à été donné', __CLASS__));
        }

        $charsCount = (int)substr($outNumber, -2);
        $outNumber  = (int)substr($outNumber, 0, strlen($outNumber) - 2);

        if ($charsCount !== strlen(self::$chars)) {
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
     * @param string  $chars
     * @return void
     * @throws \Kwaadpepper\Serial\Exceptions\SerialCasterException If parameters are wrong.
     */
    private static function init(int $number, int $length, string $chars): void
    {
        if ($length <= 0) {
            throw new SerialCasterException(sprintf('%s need a length of minimum 1', __CLASS__));
        }
        self::setChars($chars);
        if (strlen(self::$chars) < 2) {
            throw new SerialCasterException(sprintf('%s need a minimum length of 2 unique chars', __CLASS__));
        }
        if (strlen(self::$chars) > 99) {
            throw new SerialCasterException(sprintf('%s can have a minimum length of 99 unique chars', __CLASS__));
        }
        $minimumLength = self::calculateNewBaseLengthFromBase10($number, strlen(self::$chars)) + 2;
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
    private static function shuffle(int $seed, string &$serial): void
    {
        if ($seed) {
            self::setupShuffle($seed);
            self::$shuffler->shuffle($serial);
            self::rotateLeft($serial, self::sumString($serial) % strlen($serial));
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
    private static function unshuffle(int $seed, string &$serial): void
    {
        if ($seed) {
            self::setupShuffle($seed);
            self::rotateRight($serial, self::sumString($serial) % strlen($serial));
            self::$shuffler->unshuffle($serial);
        }
    }

    /**
     * Setup shuffle
     *
     * @param integer $seed
     * @return void
     */
    private static function setupShuffle(int $seed): void
    {
        if (!self::$shuffler) {
            self::$shuffler = new FisherYatesShuffler($seed);
            return;
        }
        if (self::$shuffler->seed() !== $seed) {
            self::$shuffler = new FisherYatesShuffler($seed);
        }
    }

    /**
     * Setup char dict
     *
     * @param string $chars
     * @return void
     */
    private static function setChars(string $chars): void
    {
        if (strlen($chars)) {
            self::$chars = count_chars($chars, 3);
            return;
        }

        self::setupDefaultChars();
    }

    /**
     * Setup default chars
     *
     * @return void
     */
    private static function setupDefaultChars(): void
    {
        $defaultChars = implode(range('a', 'z')) .
        implode(range('A', 'Z')) .
        implode(range('0', '9'));

        self::$chars = count_chars($defaultChars, 3);
    }

    /**
     * Choisit et initialise le moteur de conversion approprié.
     *
     * @return void
     */
    private static function setupConverter(): void
    {
        if (!self::$converter) {
            if (extension_loaded('gmp')) {
                self::$converter = new GmpBaseConverter();
            } elseif (extension_loaded('bcmath')) {
                self::$converter = new BCMathBaseConverter();
            } else {
                self::$converter = new NativeBaseConverter();
            }
        }
    }

    /**
     * Converts any number from a base to another using chars
     *
     * @param string $numberInput
     * @param string $fromBaseInput
     * @param string $toBaseInput
     * @return string
     * @url https://www.php.net/manual/fr/function.base-convert.php#106546
     */
    private static function convBase(string $numberInput, string $fromBaseInput, string $toBaseInput): string
    {
        self::setupConverter();

        return self::$converter->convert(
            $numberInput,
            $fromBaseInput,
            $toBaseInput
        );
    }

    /**
     * Calculate the length of a decimal number in a new base length
     *
     * @param integer $number
     * @param integer $base
     * @return integer
     */
    private static function calculateNewBaseLengthFromBase10(int $number, int $base): int
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
    private static function rotateLeft(string &$string, int $distance): void
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
    private static function rotateRight(string &$string, int $distance): void
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
    private static function sumString(string $string): int
    {
        return array_sum(array_map('ord', str_split($string)));
    }
}
