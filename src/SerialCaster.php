<?php

namespace Kwaadpepper\Serial;

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

    /**
     * Available chars for Serial generation
     *
     * @var string
     */
    private static $chars = '';

    /**
     * Encode an integer using chars generating a serial of a minimum length
     *
     * Note: if $seed is equal to 0 it wont be used
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
        $outString = (string)$number;
        self::init($number, $length, $chars);
        $charsCount = str_pad(strlen(self::$chars), 2, '0', \STR_PAD_LEFT);
        $outString .= $charsCount;
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
     * Note: if $seed is equal to 0 it wont be used
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
        $outNumber    = self::convBase($serial, self::$chars, self::BASE10);
        for ($i = 0; $i < $serialLength; $i++) {
            if (strpos(self::$chars, $serial[$i]) === false) {
                throw new SerialCasterException(sprintf(
                    '%s::decode un caractère non valide `%s` est présent',
                    __CLASS__,
                    $serial[$i]
                ));
            }
        }
        if (strlen($outNumber) < 3) {
            throw new SerialCasterException(sprintf('%s::decode un code série invalide à été donné', __CLASS__));
        }
        $charsCount = (int)substr($outNumber, -2);
        $outNumber  = (int)substr($outNumber, 0, strlen($outNumber) - 2);
        if ($charsCount !== strlen(self::$chars)) {
            throw new SerialCasterException(sprintf(
                // phpcs:ignore Generic.Files.LineLength.TooLong
                '%s::decode la liste de caractère pour décoder ne semble pas correspondre à celui utilisé pour l\'encodage',
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
            // Keep a string of unique chars.
            self::$chars = count_chars($chars, 3);
            return;
        }
        $inits = [
            [ord('a'), ord('z')],
            [ord('A'), ord('Z')],
            [ord('0'), ord('9')],
        ];

        self::$chars = "";
        foreach ($inits as $init) {
            for ($i = $init[0]; $i <= $init[1]; $i++) {
                self::$chars .= chr($i);
            }
        }
        self::$chars = count_chars(self::$chars, 3);
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
        if ($fromBaseInput == $toBaseInput) {
            return $numberInput;
        }
        $fromBase  = str_split($fromBaseInput, 1);
        $toBase    = str_split($toBaseInput, 1);
        $number    = str_split($numberInput, 1);
        $fromLen   = strlen($fromBaseInput);
        $toLen     = strlen($toBaseInput);
        $numberLen = strlen($numberInput);
        $retval    = '';
        if ($toBaseInput == self::BASE10) {
            $retval = 0;
            for ($i = 1; $i <= $numberLen; $i++) {
                $retval = bcadd($retval, bcmul(
                    array_search($number[$i - 1], $fromBase),
                    bcpow($fromLen, $numberLen - $i)
                ));
            }
            return $retval;
        }
        if ($fromBaseInput != self::BASE10) {
            $base10 = self::convBase($numberInput, $fromBaseInput, self::BASE10);
        } else {
            $base10 = $numberInput;
        }
        if ($base10 < strlen($toBaseInput)) {
            return $toBase[$base10];
        }
        while ($base10 != '0') {
            $retval = $toBase[bcmod($base10, $toLen)] . $retval;
            $base10 = bcdiv($base10, $toLen, 0);
        }
        return $retval;
    }

    /**
     * Calculate the length of a decimal number in a new base length
     *
     * @param integer $number
     * @param integer $base
     * @return integer
     * @url https://www.geeksforgeeks.org/given-number-n-decimal-base-find-number-digits-base-base-b/
     */
    private static function calculateNewBaseLengthFromBase10(int $number, int $base): int
    {
        return (int)(floor(log($number) / log($base)) + 1);
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
        $string = str_split($string);
        for ($i = 0; $i < $distance; $i++) {
            $value = $string[$i];
            unset($string[$i]);
            $string[] = $value;
        }
        $string = implode($string);
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
        $string = str_split($string);
        for ($i = 0; $i < $distance; $i++) {
            array_unshift($string, array_pop($string));
        }
        $string = implode($string);
    }

    /**
     * Sum all string chars values
     *
     * @param string $string
     * @return integer
     */
    private static function sumString(string $string): int
    {
        $o      = 0;
        $length = \strlen($string);
        for ($i = $length - 1; $i >= 0; $i--) {
            $o += ord($string[$i]);
        }
        return $o;
    }
}
