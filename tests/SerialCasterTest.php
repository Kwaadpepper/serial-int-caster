<?php

namespace Tests;

use Kwaadpepper\Serial\Converters\BaseConverter;
use Kwaadpepper\Serial\Converters\BCMathBaseConverter;
use Kwaadpepper\Serial\Converters\GmpBaseConverter;
use Kwaadpepper\Serial\Exceptions\ConfigurationException;
use Kwaadpepper\Serial\Exceptions\InvalidSerialException;
use Kwaadpepper\Serial\SerialCaster;
use Kwaadpepper\Serial\SerialCasterBuilder;
use Kwaadpepper\Serial\Shufflers\FisherYatesShuffler;
use Kwaadpepper\Serial\Shufflers\Shuffler;
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
                function (?int $seed = null, ?int $length = null, ?string $chars = null): SerialCaster {
                    return $this->buildSerialCaster(
                        new BCMathBaseConverter(),
                        new FisherYatesShuffler(),
                        $seed,
                        $length,
                        $chars
                    );
                },
            ],
            'GmpBaseConverter' => [
                function (?int $seed = null, ?int $length = null, ?string $chars = null): SerialCaster {
                    return $this->buildSerialCaster(
                        new GmpBaseConverter(),
                        new FisherYatesShuffler(),
                        $seed,
                        $length,
                        $chars
                    );
                },
            ]
        ];
    }

    /**
     * Test integer encodes to string
     *
     * @dataProvider casterProvider
     * @param \Closure $casterFactory
     * @return void
     */
    public function testSerialEncodeZero(\Closure $casterFactory)
    {
        // * GIVEN
        $seed           = 0;
        $length         = self::LENGTH;
        $chars          = self::ALPHANUMERIC;
        $caster         = $casterFactory($seed, $length, $chars);
        $number         = 0;
        $expectedSerial = '000010';

        // * WHEN
        $serial = $caster->encode($number);

        // * THEN
        $this->assertEquals(
            $expectedSerial,
            $serial,
            'Encoding 0(10) on base with ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 should give 000010'
        );
    }

    /**
     * Test integer encodes to string
     *
     * @dataProvider casterProvider
     * @param \Closure $casterFactory
     * @return void
     */
    public function testSerialEncode(\Closure $casterFactory)
    {
        // * GIVEN
        $seed           = 0;
        $length         = self::LENGTH;
        $chars          = self::ALPHANUMERIC;
        $caster         = $casterFactory($seed, $length, $chars);
        $number         = 14776335;
        $expectedSerial = '1bzzzO';

        // * WHEN
        $serial = $caster->encode($number);

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
     * @param \Closure $casterFactory
     * @return void
     */
    public function testSerialEncodeWithDefaultDict(\Closure $casterFactory)
    {
        // * GIVEN
        $number         = 14776335;
        $expectedSerial = '1bzzzO';
        $seed           = 0;
        $length         = self::LENGTH;
        $chars          = self::ALPHANUMERIC;
        $caster         = $casterFactory($seed, $length, $chars);

        // * WHEN
        $serial = $caster->encode($number);

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
     * @param \Closure $casterFactory
     * @return void
     */
    public function testSerialDecode(\Closure $casterFactory)
    {
        // * GIVEN
        $seed           = 0;
        $chars          = self::ALPHANUMERIC;
        $caster         = $casterFactory($seed, null, $chars);
        $serial         = '000HLC';
        $expectedNumber = 666;

        // * WHEN
        $number = $caster->decode($serial);

        // * THEN
        $this->assertEquals(
            $expectedNumber,
            $number,
            //  phpcs:ignore Generic.Files.LineLength.TooLong
            'Decoding 000HLC(36) on base with ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 should give 666'
        );
    }

    /**
     * Tests String decode to integer wth default dict
     *
     * @dataProvider casterProvider
     * @param \Closure $casterFactory
     * @return void
     */
    public function testSerialDecodeWithDefaultDict(\Closure $casterFactory)
    {
        // * GIVEN
        $serial         = '000HLC';
        $expectedNumber = 666;
        $seed           = 0;
        $defaultCaster  = $casterFactory($seed);

        // * WHEN
        $number = $defaultCaster->decode($serial);

        // * THEN
        $this->assertEquals(
            $expectedNumber,
            $number,
            //  phpcs:ignore Generic.Files.LineLength.TooLong
            'Decoding 000HLC(36) on base with ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 should give 666'
        );
    }

    /**
     * Tests if a serial has a char not in dictm it throws an error
     *
     * @dataProvider casterProvider
     * @param \Closure $casterFactory
     * @return void
     */
    public function testBreakIfSerialHasInvalidChar(\Closure $casterFactory)
    {
        // * GIVEN
        $caster = $casterFactory();
        $serial = '*';

        // * THEN
        $this->expectException(InvalidSerialException::class);
        $this->expectExceptionMessageMatches('/Un caractère non valide `\*` est présent/');

        // * WHEN
        $caster->decode($serial);
    }

    /**
     * Tests if a serial is too shortm it throws and error
     *
     * @dataProvider casterProvider
     * @param \Closure $casterFactory
     * @return void
     */
    public function testBreakIfDecodedIsTooShort(\Closure $casterFactory)
    {
        // * GIVEN
        $seed   = self::SEED;
        $length = self::LENGTH;
        $chars  = self::ALPHANUMERIC;
        $caster = $casterFactory($seed, $length, $chars);
        $serial = 'A';

        // * THEN
        $this->expectException(InvalidSerialException::class);
        $this->expectExceptionMessageMatches('/Le code série est invalide/');

        // * WHEN
        $caster->decode($serial);
    }

    /**
     * Tests throws and error if using different dicts between encode and decode
     *
     * @dataProvider casterProvider
     * @param \Closure $casterFactory
     * @return void
     */
    public function testBreakIfDecodedCharListIsDifferentThanTheOneUsedForEncoding(\Closure $casterFactory)
    {
        // * GIVEN
        $seed          = self::SEED;
        $length        = 26;
        $encoderChars  = '01';
        $decoderChars  = self::ALPHANUMERIC;
        $encoderCaster = $casterFactory($seed, $length, $encoderChars);
        $decoderCaster = $casterFactory($seed, $length, $decoderChars);
        $number        = 14776335;

        $serial = $encoderCaster->encode($number);

        // * THEN
        $this->expectException(InvalidSerialException::class);
        $this->expectExceptionMessageMatches('/La liste de caractères pour décoder ne semble/');

        // * WHEN
        $decoderCaster->decode($serial);
    }

    /**
     * Tests if encode would throw an error if serial length is not high enough.
     *
     * @dataProvider casterProvider
     * @param \Closure $casterFactory
     * @return void
     */
    public function testBreakIfLengthIsNotHighEnough(\Closure $casterFactory)
    {
        // * GIVEN
        $seed   = self::SEED;
        $length = 5;
        $chars  = self::ALPHANUMERIC;
        $number = 14776336;
        $caster = $casterFactory($seed, $length, $chars);

        // * THEN
        $this->expectException(ConfigurationException::class);

        // * WHEN
        $caster->encode($number);
    }

    /**
     * Tests if encode would throw an error if dict is not long enough.
     *
     * @dataProvider casterProvider
     * @param \Closure $casterFactory
     * @return void
     */
    public function testBreakIfCharListIsTooShort(\Closure $casterFactory)
    {
        // * GIVEN
        $seed            = self::SEED;
        $length          = self::LENGTH;
        $chars           = '01';
        $number          = 14776336;
        $shortDictCaster = $casterFactory($seed, $length, $chars);

        // * THEN
        $this->expectException(ConfigurationException::class);

        // * WHEN
        $shortDictCaster->encode($number);
    }

    /**
     * Tests encode and decode random values
     *
     * @dataProvider casterProvider
     * @param \Closure $casterFactory
     * @return void
     */
    public function testEncodeAndDecodeWithRandomValues(\Closure $casterFactory)
    {
        // * GIVEN
        $seed   = self::SEED;
        $length = self::LENGTH;
        $chars  = self::ALPHANUMERIC;
        $caster = $casterFactory($seed, $length, $chars);
        srand();
        $loops = rand(50, 70);

        for ($i = 0; $i <= $loops; $i++) {
            srand();
            $randomInteger = rand(0, 999999);

            // * WHEN
            $serial        = $caster->encode($randomInteger);
            $decodedNumber = $caster->decode($serial);

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
     * @param \Closure $casterFactory
     * @return void
     */
    public function testSpeedTestOnCouponGeneration(\Closure $casterFactory)
    {
        // * GIVEN
        $seed                    = self::SEED;
        $length                  = self::LENGTH;
        $chars                   = self::ALPHANUMERIC;
        $caster                  = $casterFactory($seed, $length, $chars);
        $acceptableTimeInSeconds = 4;
        $numberOfCoupons         = 999;
        $coupons                 = [];

        // * WHEN
        $start = hrtime(true);
        for ($i = 0; $i <= $numberOfCoupons; $i++) {
            srand();
            $randomInteger = rand(0, $numberOfCoupons);
            $coupons[]     = $caster->encode($randomInteger);
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
     * @param \Closure $casterFactory
     * @return void
     */
    public function testSpeedTestOnCouponDecode(\Closure $casterFactory)
    {
        // * GIVEN
        $seed                    = self::SEED;
        $length                  = self::LENGTH;
        $chars                   = self::ALPHANUMERIC;
        $caster                  = $casterFactory($seed, $length, $chars);
        $acceptableTimeInSeconds = 4;
        $numberOfCoupons         = 999;
        $coupons                 = [];
        for ($i = 1; $i <= $numberOfCoupons; $i++) {
            srand();
            $randomInteger = rand(1, $numberOfCoupons);
            $coupons[]     = $caster->encode($randomInteger);
        }
        $numberOfCoupons = count($coupons);
        $decodedCoupons  = [];

        // * WHEN
        $start = hrtime(true);
        for ($i = 0; $i < $numberOfCoupons; $i++) {
            $decodedCoupons[] = $caster->decode($coupons[$i]);
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

    /**
     * Build a SerialCaster instance
     *
     * @param \Kwaadpepper\Serial\Converters\BaseConverter $converter
     * @param \Kwaadpepper\Serial\Shufflers\Shuffler|null  $shuffler
     * @param integer|null                                 $seed
     * @param integer|null                                 $length
     * @param string|null                                  $chars
     * @return \Kwaadpepper\Serial\SerialCaster
     */
    private function buildSerialCaster(
        BaseConverter $converter,
        ?Shuffler $shuffler = null,
        ?int $seed = null,
        ?int $length = null,
        ?string $chars = null
    ): SerialCaster {
        $builder = (new SerialCasterBuilder($converter))
            ->withShuffler($shuffler);

        if ($seed !== null) {
            $builder->withSeed($seed);
        }
        if ($length !== null) {
            $builder->withLength($length);
        }
        if ($chars !== null) {
            $builder->withChars($chars);
        }

        return $builder->build();
    }
}
