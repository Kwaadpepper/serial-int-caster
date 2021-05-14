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

    private const BASE10 = '0123456789';

    private static $length = 6;

    /**
     * Available chars for Serial generation
     *
     * @var string
     */
    private static $chars = '';

    /**
     * Encode an integer using chars generating a serial of a minimum length
     *
     * @param integer $number
     * @param integer $length
     * @param string $chars
     * @return string
     * @throws SerialCasterException if a config error happens
     */
    public static function encode(int $number, int $length = 6, string $chars = ''): string
    {
        $outString = (string)$number;
        self::init($number, $length, $chars);
        $charsCount = str_pad(strlen(self::$chars), 2, '0', \STR_PAD_LEFT);
        $outString .= $charsCount;
        $outString = str_pad(
            self::convBase($outString, self::BASE10, self::$chars),
            $length,
            self::$chars[0],
            \STR_PAD_LEFT
        );
        return $outString;
    }

    /**
     * Decode an integer using chars
     *
     * @param string $serial
     * @param string $chars
     * @return integer
     * @throws SerialCasterException if a wrong serial or charlist is given
     */
    public static function decode(string $serial, string $chars = ''): int
    {
        self::setChars($chars);
        $serialLength = strlen($serial);
        $outNumber = self::convBase($serial, self::$chars, self::BASE10);
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
        $outNumber = (int)substr($outNumber, 0, strlen($outNumber) - 2);
        if ($charsCount !== strlen(self::$chars)) {
            throw new SerialCasterException(sprintf(
                // phpcs:ignore Generic.Files.LineLength.TooLong
                '%s::decode la liste de caractère pour décoder ne semble pas correspondre à celui utilisé pour l\'encodage',
                __CLASS__
            ));
        }
        return $outNumber;
    }

    private static function init(int $number, int $length, string $chars)
    {
        self::setChars($chars);
        if (strlen(self::$chars) < 2) {
            throw new SerialCasterException(sprintf('%s need a minimum length of 2 unique chars', __CLASS__));
        }
        if (strlen(self::$chars) > 99) {
            throw new SerialCasterException(sprintf('%s can have a minimum length of 99 unique chars', __CLASS__));
        }
        $minimumLength = self::calculateNewBaseLengthFromBase10($number, strlen(self::$chars)) + 2;
        if ((self::$length = $length) < $minimumLength) {
            throw new SerialCasterException(sprintf(
                '%s need a minimum length of %d',
                __CLASS__,
                $minimumLength
            ));
        }
    }

    private static function setChars(string $chars)
    {
        if (strlen($chars)) {
            // Keep a string of unique chars
            self::$chars = count_chars($chars, 3);
        }
        if (!strlen(self::$chars)) {
            $inits = [
                [ord('a'), ord('z')],
                [ord('A'), ord('Z')],
                [ord('0'), ord('9')],
            ];
            foreach ($inits as $init) {
                for ($i = $init[0]; $i <= $init[1]; $i++) {
                    self::$chars .= chr($i);
                }
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
        if ($fromBaseInput == $toBaseInput) {
            return $numberInput;
        }
        $fromBase = str_split($fromBaseInput, 1);
        $toBase = str_split($toBaseInput, 1);
        $number = str_split($numberInput, 1);
        $fromLen = strlen($fromBaseInput);
        $toLen = strlen($toBaseInput);
        $numberLen = strlen($numberInput);
        $retval = '';
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
     * @return int
     * @url https://www.geeksforgeeks.org/given-number-n-decimal-base-find-number-digits-base-base-b/
     */
    private static function calculateNewBaseLengthFromBase10(int $number, int $base)
    {
        return (int)(floor(log($number) / log($base)) + 1);
    }
}
