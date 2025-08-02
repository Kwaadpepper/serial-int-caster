<?php

namespace Tests;

use Kwaadpepper\Serial\Converters\BCMathBaseConverter;
use Kwaadpepper\Serial\Converters\GmpBaseConverter;
use Kwaadpepper\Serial\Exceptions\ConfigurationException;
use Kwaadpepper\Serial\Exceptions\InvalidSerialException;
use Kwaadpepper\Serial\SerialCaster;
use Kwaadpepper\Serial\Shufflers\FisherYatesShuffler;
use PHPUnit\Framework\TestCase;

class SerialCasterTest extends TestCase
{
    private const ALPHANUMERIC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    private const LENGTH       = 6;
    private const SEED         = 1492;

    /**
     * Data provider for SerialCaster
     *
     * @return array
     */
    public function casterProvider(): array
    {
        return [
            'BCMathBaseConverter' => [
                new SerialCaster(new BCMathBaseConverter(), new FisherYatesShuffler(), self::ALPHANUMERIC),
                BCMathBaseConverter::class
            ],
            'GmpBaseConverter' => [
                new SerialCaster(new GmpBaseConverter(), new FisherYatesShuffler(), self::ALPHANUMERIC),
                GmpBaseConverter::class
            ]
        ];
    }

    /**
     * Test integer encodes to string
     *
     * @dataProvider casterProvider
     * @param \Kwaadpepper\Serial\SerialCaster $caster
     * @return void
     */
    public function testSerialEncodeZero(SerialCaster $caster)
    {
        // * GIVEN
        $number         = 0;
        $seed           = 0;
        $length         = self::LENGTH;
        $expectedSerial = '000010';

        // * WHEN
        $serial = $caster->encode($number, $seed, $length);

        // * THEN
        $this->assertEquals(
            $expectedSerial,
            $serial,
            //  phpcs:ignore Generic.Files.LineLength.TooLong
            'Encoding 0(10) on base with ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 should give 000010'
        );
    }

    /**
     * Test integer encodes to string
     *
     * @dataProvider casterProvider
     * @param \Kwaadpepper\Serial\SerialCaster $caster
     * @return void
     */
    public function testSerialEncode(SerialCaster $caster)
    {
        // * GIVEN
        $number         = 14776335;
        $seed           = 0;
        $length         = self::LENGTH;
        $expectedSerial = '1bzzzO';

        // * WHEN
        $serial = $caster->encode($number, $seed, $length);

        // * THEN
        $this->assertEquals(
            $expectedSerial,
            $serial,
            //  phpcs:ignore Generic.Files.LineLength.TooLong
            'Encoding 14776335(10) on base with ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 should give 1bzzzO'
        );
    }

    /**
     * Test integer encodes to string with default dict
     *
     * @dataProvider casterProvider
     * @param \Kwaadpepper\Serial\SerialCaster $caster
     * @param string                           $converterClass
     * @return void
     *
     * @phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed
     */
    public function testSerialEncodeWithDefaultDict(SerialCaster $caster, string $converterClass)
    {
        // phpcs:enable
        // * GIVEN
        $number         = 14776335;
        $seed           = 0;
        $length         = self::LENGTH;
        $expectedSerial = '1bzzzO';
        $customCaster   = new SerialCaster(new $converterClass(), new FisherYatesShuffler());

        // * WHEN
        $serial = $customCaster->encode($number, $seed, $length);

        // * THEN
        $this->assertEquals(
            $expectedSerial,
            $serial,
            //  phpcs:ignore Generic.Files.LineLength.TooLong
            'Encoding 14776335(10) on base with ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 should give 1bzzzO'
        );
    }

    /**
     * Tests String decode to integer
     *
     * @dataProvider casterProvider
     * @param \Kwaadpepper\Serial\SerialCaster $caster
     * @return void
     */
    public function testSerialDecode(SerialCaster $caster)
    {
        // * GIVEN
        $serial         = '000HLC';
        $seed           = 0;
        $expectedNumber = 666;

        // * WHEN
        $number = $caster->decode($serial, $seed);

        // * THEN
        $this->assertEquals(
            $expectedNumber,
            $number,
        );
    }

    /**
     * Tests String decode to integer wth default dict
     *
     * @dataProvider casterProvider
     * @param \Kwaadpepper\Serial\SerialCaster $caster
     * @param string                           $converterClass
     * @return void
     *
     * @phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed
     */
    public function testSerialDecodeWithDefaultDict(SerialCaster $caster, string $converterClass)
    {
        // phpcs:enable
        // * GIVEN
        $serial         = '000HLC';
        $seed           = 0;
        $expectedNumber = 666;
        $defaultCaster  = new SerialCaster(new $converterClass(), new FisherYatesShuffler());

        // * WHEN
        $number = $defaultCaster->decode($serial, $seed);

        // * THEN
        $this->assertEquals(
            $expectedNumber,
            $number
        );
    }

    /**
     * Tests if a serial has a char not in dictm it throws an error
     *
     * @dataProvider casterProvider
     * @param \Kwaadpepper\Serial\SerialCaster $caster
     * @return void
     */
    public function testBreakIfSerialHasInvalidChar(SerialCaster $caster)
    {
        // * GIVEN
        $serial = '*';
        $seed   = self::SEED;

        // * THEN
        $this->expectException(InvalidSerialException::class);
        $this->expectExceptionMessageMatches('/Un caractère non valide `\*` est présent/');

        // * WHEN
        $caster->decode($serial, $seed);
    }

    /**
     * Tests if a serial is too shortm it throws and error
     *
     * @dataProvider casterProvider
     * @param \Kwaadpepper\Serial\SerialCaster $caster
     * @return void
     */
    public function testBreakIfDecodedIsTooShort(SerialCaster $caster)
    {
        // * GIVEN
        $serial = 'A';
        $seed   = self::SEED;

        // * THEN
        $this->expectException(InvalidSerialException::class);
        $this->expectExceptionMessageMatches('/Le code série est invalide/');

        // * WHEN
        $caster->decode($serial, $seed);
    }

    /**
     * Tests throws and error if using different dicts between encode and decode
     *
     * @dataProvider casterProvider
     * @param \Kwaadpepper\Serial\SerialCaster $ignored
     * @param string                           $converterClass
     * @return void
     *
     * @phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed
     */
    public function testBreakIfDecodedCharListIsDifferentThanTheOneUsedForEncoding(
        SerialCaster $ignored,
        string $converterClass
    ) {
        // phpcs:enable
        // * GIVEN
        $seed          = self::SEED;
        $shuffler      = new FisherYatesShuffler();
        $encoderCaster = new SerialCaster(new $converterClass(), $shuffler, '01');
        $decoderCaster = new SerialCaster(new $converterClass(), $shuffler, self::ALPHANUMERIC);
        $serial        = $encoderCaster->encode(14776335, $seed, 26);

        // * THEN
        $this->expectException(InvalidSerialException::class);
        $this->expectExceptionMessageMatches('/La liste de caractères pour décoder ne semble/');

        // * WHEN
        $decoderCaster->decode($serial, $seed);
    }

    /**
     * Tests if encode would throw an error if serial length is not high enough.
     *
     * @dataProvider casterProvider
     * @param \Kwaadpepper\Serial\SerialCaster $caster
     * @return void
     */
    public function testBreakIfLengthIsNotHighEnough(SerialCaster $caster)
    {
        // * GIVEN
        $number = 14776336;
        $seed   = self::SEED;
        $length = self::LENGTH;

        // * THEN
        $this->expectException(ConfigurationException::class);

        // * WHEN
        $caster->encode($number, $seed, $length);
    }

    /**
     * Tests if encode would throw an error if dict is not long enough.
     *
     * @dataProvider casterProvider
     * @param \Kwaadpepper\Serial\SerialCaster $ignored
     * @param string                           $converterClass
     * @return void
     *
     * @phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed
     */
    public function testBreakIfCharListIsTooShort(SerialCaster $ignored, string $converterClass)
    {
        // phpcs:enable
        // * GIVEN
        $number          = 14776336;
        $seed            = self::SEED;
        $length          = self::LENGTH;
        $shortDictCaster = new SerialCaster(
            new $converterClass(),
            new FisherYatesShuffler(),
            '01'
        );

        // * THEN
        $this->expectException(ConfigurationException::class);

        // * WHEN
        $shortDictCaster->encode($number, $seed, $length);
    }

    /**
     * Tests encode and decode random values
     *
     * @dataProvider casterProvider
     * @param \Kwaadpepper\Serial\SerialCaster $caster
     * @return void
     */
    public function testEncodeAndDecodeWithRandomValues(SerialCaster $caster)
    {
        srand();
        $loops = rand(50, 70);
        for ($i = 0; $i <= $loops; $i++) {
            // * GIVEN
            srand();
            $randomInteger = rand(0, 999999);
            $seed          = self::SEED;
            $length        = self::LENGTH;

            // * WHEN
            $serial        = $caster->encode($randomInteger, $seed, $length);
            $decodedNumber = $caster->decode($serial, $seed);

            // * THEN
            $this->assertEquals(
                $randomInteger,
                $decodedNumber
            );
        }
    }

    /**
     * Tests speed creating coupons.
     *
     * @dataProvider casterProvider
     * @param \Kwaadpepper\Serial\SerialCaster $caster
     * @return void
     */
    public function testSpeedTestOnCouponGeneration(SerialCaster $caster)
    {
        // * GIVEN
        $acceptableTimeInSeconds = 4;
        $numberOfCoupons         = 999;
        $coupons                 = [];

        // * WHEN
        $start = hrtime(true);
        for ($i = 0; $i <= $numberOfCoupons; $i++) {
            srand();
            $randomInteger = rand(0, $numberOfCoupons);
            $coupons[]     = $caster->encode($randomInteger, self::SEED, self::LENGTH);
        }
        $end = hrtime(true);

        // * THEN
        $this->assertLessThan(
            $acceptableTimeInSeconds,
            ($end - $start) / 1e+9,
            sprintf(
                'Encoding %d coupons took more than %d seconds (%f)',
                $numberOfCoupons,
                $acceptableTimeInSeconds,
                ($end - $start) / 1e+9
            )
        );
    }

    /**
     * Tests speed reading coupons.
     *
     * @dataProvider casterProvider
     * @param \Kwaadpepper\Serial\SerialCaster $caster
     * @return void
     */
    public function testSpeedTestOnCouponDecode(SerialCaster $caster)
    {
        // * GIVEN
        $acceptableTimeInSeconds = 4;
        $numberOfCoupons         = 999;
        $coupons                 = [];
        for ($i = 1; $i <= $numberOfCoupons; $i++) {
            srand();
            $randomInteger = rand(1, $numberOfCoupons);
            $coupons[]     = $caster->encode($randomInteger, self::SEED, self::LENGTH);
        }
        $numberOfCoupons = count($coupons);
        $decodedCoupons  = [];

        // * WHEN
        $start = hrtime(true);
        for ($i = 0; $i < $numberOfCoupons; $i++) {
            $decodedCoupons[] = $caster->decode($coupons[$i], self::SEED);
        }
        $end = hrtime(true);

        // * THEN
        $this->assertLessThan(
            $acceptableTimeInSeconds,
            ($end - $start) / 1e+9,
            sprintf(
                'Decoding %d coupons took more than %d seconds (%f)',
                $numberOfCoupons,
                $acceptableTimeInSeconds,
                ($end - $start) / 1e+9
            )
        );
    }
}
