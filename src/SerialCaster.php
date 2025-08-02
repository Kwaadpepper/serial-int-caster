<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial;

use Kwaadpepper\Serial\Converters\BaseConverter;
use Kwaadpepper\Serial\Exceptions\ConfigurationException;
use Kwaadpepper\Serial\Exceptions\InvalidSerialException;
use Kwaadpepper\Serial\Shufflers\Shuffler;

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

    /** @var \Kwaadpepper\Serial\Converters\BaseConverter Le moteur de conversion de base. */
    private $converter;

    /** @var \Kwaadpepper\Serial\Shufflers\Shuffler|null */
    private $shuffler;

    /**
     * Available chars for Serial generation
     *
     * @var array<int, string>
     */
    private $chars = [];

    /** @var integer La graine de mélange utilisée. */
    private $seed;

    /** @var integer La longueur minimale des numéros de série. */
    private $length;

    /**
     * SerialCaster constructor.
     *
     * @param \Kwaadpepper\Serial\Converters\BaseConverter $converter
     * @param \Kwaadpepper\Serial\Shufflers\Shuffler|null  $shuffler
     * @param string|null                                  $chars
     * @param integer                                      $seed
     * @param integer                                      $length
     * @throws \Kwaadpepper\Serial\Exceptions\ConfigurationException If the chars are not valid.
     */
    public function __construct(
        BaseConverter $converter,
        ?Shuffler $shuffler = null,
        ?string $chars = null,
        int $seed = 0,
        int $length = 6
    ) {
        $this->converter = $converter;
        $this->shuffler  = $shuffler;
        $this->seed      = $seed;
        $this->length    = $length;
        $this->setChars($chars);
    }

    /**
     * Encode an integer using chars generating a serial of a minimum length
     *
     * @param integer $number The number to encode in the serial string.
     * @return string The serial
     * @throws \Kwaadpepper\Serial\Exceptions\ConfigurationException If a config error happens.
     */
    public function encode(int $number): string
    {
        $this->validateEncodingParameters($number, $this->length);

        $charsCount   = str_pad((string)count($this->chars), 2, '0', \STR_PAD_LEFT);
        $paddedNumber = (string)$number . $charsCount;

        $convertedSerial = $this->convBase($paddedNumber, self::BASE10, $this->chars);

        $paddedSerial = str_pad(
            $convertedSerial,
            $this->length,
            $this->chars[0],
            \STR_PAD_LEFT
        );

        $this->shuffle($this->seed, $paddedSerial);

        return $paddedSerial;
    }

    /**
     * Decode an integer using chars
     *
     * @param string $serial The serial ton decode.
     * @return integer The number encoded in the serial
     * @throws \Kwaadpepper\Serial\Exceptions\ConfigurationException If a wrong serial or charlist is given.
     * @throws \Kwaadpepper\Serial\Exceptions\InvalidSerialException If the serial is invalid.
     * @throws \Kwaadpepper\Serial\Exceptions\InvalidNumberException If the number of chars does not match the
     *  expected count.
     *
     * @phpcs:ignore Squiz.Commenting.FunctionCommentThrowTag.WrongNumber
     */
    public function decode(string $serial): int
    {
        $this->unshuffle($this->seed, $serial);
        $this->validateSerialChars($serial);

        $decodedString = $this->convBase($serial, $this->chars, self::BASE10);
        $this->validateDecodedString($decodedString);

        $charsCountFromSerial = (int)substr($decodedString, -2);
        $encodedNumber        = (int)substr($decodedString, 0, strlen($decodedString) - 2);

        if ($charsCountFromSerial !== count($this->chars)) {
            throw new InvalidSerialException(
                'La liste de caractères pour décoder ne semble pas correspondre à celle utilisée pour l\'encodage.'
            );
        }

        return $encodedNumber;
    }

    /**
     * Validate the characters in the serial.
     *
     * @param string $serial The serial to validate.
     * @return void
     * @throws \Kwaadpepper\Serial\Exceptions\InvalidSerialException If an invalid character is found.
     */
    private function validateSerialChars(string $serial): void
    {
        $serialLength = strlen($serial);
        for ($i = 0; $i < $serialLength; $i++) {
            if (!in_array($serial[$i], $this->chars, true)) {
                throw new InvalidSerialException(sprintf(
                    'Un caractère non valide `%s` est présent dans la série.',
                    $serial[$i]
                ));
            }
        }
    }

    /**
     * Validate the decoded string.
     *
     * @param string $decodedString The decoded string to validate.
     * @return void
     * @throws \Kwaadpepper\Serial\Exceptions\InvalidSerialException If the decoded string is too short.
     */
    private function validateDecodedString(string $decodedString): void
    {
        if (strlen($decodedString) < 3) {
            throw new InvalidSerialException('Le code série est invalide.');
        }
    }

    /**
     * Validate encoding parameters
     *
     * @param integer $number
     * @param integer $length
     * @return void
     * @throws \Kwaadpepper\Serial\Exceptions\ConfigurationException If parameters are wrong.
     */
    private function validateEncodingParameters(int $number, int $length): void
    {
        if ($length <= 0) {
            throw new ConfigurationException(sprintf('%s need a length of minimum 1', __CLASS__));
        }
        if (count($this->chars) < 2) {
            throw new ConfigurationException(sprintf('%s need a minimum length of 2 unique chars', __CLASS__));
        }
        if (count($this->chars) > 99) {
            throw new ConfigurationException(sprintf('%s can have a minimum length of 99 unique chars', __CLASS__));
        }
        $minimumLength = $this->calculateNewBaseLengthFromBase10($number, count($this->chars)) + 2;
        if ($length < $minimumLength) {
            throw new ConfigurationException(sprintf(
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
        if ($seed && $this->shuffler !== null) {
            $this->shuffler->shuffle($serial, $seed);
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
        if ($seed && $this->shuffler !== null) {
            $this->rotateRight($serial, $this->sumString($serial) % strlen($serial));
            $this->shuffler->unshuffle($serial, $seed);
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
