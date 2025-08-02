<?php

namespace Tests;

use Kwaadpepper\Serial\Converters\BCMathBaseConverter;
use Kwaadpepper\Serial\Converters\GmpBaseConverter;
use Kwaadpepper\Serial\Exceptions\SerialCasterException;
use Kwaadpepper\Serial\SerialCaster;
use PHPUnit\Framework\TestCase;

class SerialCasterTest extends TestCase
{
    private const ALPHANUMERIC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    private const LENGTH       = 6;
    private const SEED         = 1492;

    /** @var \Kwaadpepper\Serial\SerialCaster */
    private $caster;

    /**
     * Data provider for SerialCaster
     *
     * @return array
     */
    public function casterProvider(): array
    {
        return [
            'BCMathBaseConverter' => [
                new SerialCaster(new BCMathBaseConverter(), self::ALPHANUMERIC),
                BCMathBaseConverter::class
            ],
            'GmpBaseConverter' => [
                new SerialCaster(new GmpBaseConverter(), self::ALPHANUMERIC),
                GmpBaseConverter::class
            ]
        ];
    }

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->caster = new SerialCaster(
            new BCMathBaseConverter(),
            self::ALPHANUMERIC
        );
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
        $this->assertEquals(
            '000010',
            $caster->encode(0, 0, self::LENGTH),
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
        $this->assertEquals(
            '1bzzzO',
            $caster->encode(14776335, 0, self::LENGTH),
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
        // This is the default dict used in BCMathBaseConverter.
        $customCaster = new SerialCaster(new $converterClass());

        $this->assertEquals(
            '1bzzzO',
            $customCaster->encode(14776335, 0, self::LENGTH),
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
        $this->assertEquals(
            666,
            $caster->decode('000HLC', 0),
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
        // This is the default dict used in BCMathBaseConverter.
        $defaultCaster = new SerialCaster(new $converterClass());

        $this->assertEquals(
            666,
            $defaultCaster->decode('000HLC', 0)
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
        $this->expectException(SerialCasterException::class);
        $this->expectExceptionMessageMatches('/un caractère non valide `\*` est présent/');

        $caster->decode('*', self::SEED);
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
        $this->expectException(SerialCasterException::class);
        $this->expectExceptionMessageMatches('/un code série invalide à été donné/');

        $caster->decode('A', self::SEED);
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
        $encoderCaster = new SerialCaster(new $converterClass(), '01');
        $decoderCaster = new SerialCaster(new $converterClass(), self::ALPHANUMERIC);

        $serial = $encoderCaster->encode(14776335, self::SEED, 26);

        $this->expectException(SerialCasterException::class);
        $this->expectExceptionMessageMatches('/la liste de caractère pour décoder ne semble/');

        $decoderCaster->decode($serial, self::SEED);
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
        $this->expectException(SerialCasterException::class);
        // This should break.
        $caster->encode(14776336, self::SEED, self::LENGTH);
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
        $shortDictCaster = new SerialCaster(
            new $converterClass(),
            '01'
        );

        $this->expectException(SerialCasterException::class);
        // This should break.
        $shortDictCaster->encode(14776336, self::SEED, self::LENGTH);
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
            srand();
            $randomInteger = rand(0, 999999);
            $this->assertEquals(
                $randomInteger,
                $caster->decode(
                    $caster->encode($randomInteger, self::SEED, self::LENGTH),
                    self::SEED
                )
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
        $acceptableTimeInSeconds = 4;
        $numberOfCoupons         = 999;
        $coupons                 = [];
        $start                   = hrtime(true);
        for ($i = 0; $i <= $numberOfCoupons; $i++) {
            srand();
            $randomInteger = rand(0, $numberOfCoupons);
            $coupons[]     = $caster->encode($randomInteger, self::SEED, self::LENGTH);
        }
        $endtime = hrtime(true);
        $this->assertLessThan(
            $acceptableTimeInSeconds,
            ($endtime - $start) / 1e+9,
            sprintf(
                'Encoding %d coupons took more than %d seconds (%f)',
                $numberOfCoupons,
                $acceptableTimeInSeconds,
                ($endtime - $start) / 1e+9
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
        $acceptableTimeInSeconds = 4;
        $numberOfCoupons         = 999;
        $coupons                 = [];
        $decodedCoupons          = [];
        for ($i = 1; $i <= $numberOfCoupons; $i++) {
            srand();
            $randomInteger = rand(1, $numberOfCoupons);
            $coupons[]     = $caster->encode($randomInteger, self::SEED, self::LENGTH);
        }

        $numberOfCoupons = count($coupons);
        $start           = hrtime(true);
        for ($i = 0; $i < $numberOfCoupons; $i++) {
            $decodedCoupons[] = $caster->decode($coupons[$i], self::SEED);
        }
        $endtime = hrtime(true);
        $this->assertLessThan(
            $acceptableTimeInSeconds,
            ($endtime - $start) / 1e+9,
            sprintf(
                'Decoding %d coupons took more than %d seconds (%f)',
                $numberOfCoupons,
                $acceptableTimeInSeconds,
                ($endtime - $start) / 1e+9
            )
        );
    }
}
