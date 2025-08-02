<?php

namespace Tests;

use Kwaadpepper\Serial\Converters\BCMathBaseConverter;
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
     * @return void
     */
    public function testSerialEncodeZero()
    {
        $this->assertEquals(
            '000010',
            $this->caster->encode(0, 0, self::LENGTH),
            //  phpcs:ignore Generic.Files.LineLength.TooLong
            'Encoding 0(10) on base with ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 should give 000010'
        );
    }

    /**
     * Test integer encodes to string
     *
     * @return void
     */
    public function testSerialEncode()
    {
        $this->assertEquals(
            '1bzzzO',
            $this->caster->encode(14776335, 0, self::LENGTH),
            //  phpcs:ignore Generic.Files.LineLength.TooLong
            'Encoding 14776335(10) on base with ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 should give 1bzzzO'
        );
    }

    /**
     * Test integer encodes to string with default dict
     *
     * @return void
     */
    public function testSerialEncodeWithDefaultDict()
    {
        $defaultCaster = new SerialCaster(new BCMathBaseConverter());
        $this->assertEquals(
            '1bzzzO',
            $defaultCaster->encode(14776335, 0, self::LENGTH),
            //  phpcs:ignore Generic.Files.LineLength.TooLong
            'Encoding 14776335(10) on base with ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 should give 1bzzzO'
        );
    }

    /**
     * Tests String decode to integer
     *
     * @return void
     */
    public function testSerialDecode()
    {
        $this->assertEquals(
            666,
            $this->caster->decode('000HLC', 0),
        );
    }

    /**
     * Tests String decode to integer wth default dict
     *
     * @return void
     */
    public function testSerialDecodeWithDefaultDict()
    {
        // This is the default dict used in BCMathBaseConverter.
        $defaultCaster = new SerialCaster(new BCMathBaseConverter());

        $this->assertEquals(
            666,
            $defaultCaster->decode('000HLC', 0)
        );
    }

    /**
     * Tests if a serial has a char not in dictm it throws an error
     *
     * @return void
     */
    public function testBreakIfSerialHasInvalidChar()
    {
        $this->expectException(SerialCasterException::class);
        $this->expectExceptionMessageMatches('/un caractère non valide `\*` est présent/');

        $this->caster->decode('*', self::SEED);
    }

    /**
     * Tests if a serial is too shortm it throws and error
     *
     * @return void
     */
    public function testBreakIfDecodedIsTooShort()
    {
        $this->expectException(SerialCasterException::class);
        $this->expectExceptionMessageMatches('/un code série invalide à été donné/');

        $this->caster->decode('A', self::SEED);
    }

    /**
     * Tests throws and error if using different dicts between encode and decode
     *
     * @return void
     */
    public function testBreakIfDecodedCharListIsDifferentThanTheOneUsedForEncoding()
    {
        $encoderCaster = new SerialCaster(new BCMathBaseConverter(), '01');
        $decoderCaster = new SerialCaster(new BCMathBaseConverter(), self::ALPHANUMERIC);

        $serial = $encoderCaster->encode(14776335, self::SEED, 26);

        $this->expectException(SerialCasterException::class);
        $this->expectExceptionMessageMatches('/la liste de caractère pour décoder ne semble/');

        $decoderCaster->decode($serial, self::SEED);
    }

    /**
     * Tests if encode would throw an error if serial length is not high enough.
     *
     * @return void
     */
    public function testBreakIfLengthIsNotHighEnough()
    {
        $this->expectException(SerialCasterException::class);
        // This should break.
        $this->caster->encode(14776336, self::SEED, self::LENGTH);
    }

    /**
     * Tests if encode would throw an error if dict is not long enough.
     *
     * @return void
     */
    public function testBreakIfCharListIsTooShort()
    {
        $shortDictCaster = new SerialCaster(
            new BCMathBaseConverter(),
            '01'
        );

        $this->expectException(SerialCasterException::class);
        // This should break.
        $shortDictCaster->encode(14776336, self::SEED, self::LENGTH);
    }

    /**
     * Tests encode and decode random values
     *
     * @return void
     */
    public function testEncodeAndDecodeWithRandomValues()
    {
        srand();
        $loops = rand(50, 70);
        for ($i = 0; $i <= $loops; $i++) {
            srand();
            $randomInteger = rand(0, 999999);
            $this->assertEquals(
                $randomInteger,
                $this->caster->decode(
                    $this->caster->encode($randomInteger, self::SEED, self::LENGTH),
                    self::SEED
                )
            );
        }
    }

    /**
     * Tests speed creating coupons.
     *
     * @return void
     */
    public function testSpeedTestOnCouponGeneration()
    {
        $acceptableTimeInSeconds = 4;
        $numberOfCoupons         = 999;
        $coupons                 = [];
        $start                   = hrtime(true);
        for ($i = 0; $i <= $numberOfCoupons; $i++) {
            srand();
            $randomInteger = rand(0, $numberOfCoupons);
            $coupons[]     = $this->caster->encode($randomInteger, self::SEED, self::LENGTH);
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
     * @return void
     */
    public function testSpeedTestOnCouponDecode()
    {
        $acceptableTimeInSeconds = 4;
        $numberOfCoupons         = 999;
        $coupons                 = [];
        $decodedCoupons          = [];
        for ($i = 1; $i <= $numberOfCoupons; $i++) {
            srand();
            $randomInteger = rand(1, $numberOfCoupons);
            $coupons[]     = $this->caster->encode($randomInteger, self::SEED, self::LENGTH);
        }

        $numberOfCoupons = count($coupons);
        $start           = hrtime(true);
        for ($i = 0; $i < $numberOfCoupons; $i++) {
            $decodedCoupons[] = $this->caster->decode($coupons[$i], self::SEED);
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
