<?php

namespace Tests;

use Kwaadpepper\Serial\Exceptions\SerialCasterException;
use Kwaadpepper\Serial\SerialCaster;
use PHPUnit\Framework\TestCase;

class SerialCasterTest extends TestCase
{
    private const ALPHANUMERIC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    private const LENGTH = 6;
    private const SEED = 1492;

    public function testSerialEncode()
    {
        $this->assertEquals(
            SerialCaster::encode(14776335, 0, self::LENGTH, self::ALPHANUMERIC),
            '1bzzzO',
            //  phpcs:ignore Generic.Files.LineLength.TooLong
            'Encoding 14776335(10) on base with ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 should give 1bzzzO'
        );
    }

    public function testSerialDecode()
    {
        $this->assertEquals(SerialCaster::decode('000HLC', 0, self::ALPHANUMERIC), 666);
    }

    public function testBreakIfSerialHasInvalidChar()
    {
        $this->expectException(
            SerialCasterException::class,
            'If serial has an invalid char it should throw an exception'
        );
        SerialCaster::decode('*', self::SEED, self::ALPHANUMERIC);
    }

    public function testBreakIfDecodedIsTooShort()
    {
        $this->expectException(
            SerialCasterException::class,
            'If the decoded serial is too short (<=2) it should throw an exception'
        );
        SerialCaster::decode('A', self::SEED, self::ALPHANUMERIC);
    }

    public function testBreakIfDecodedCharListIsDifferentThanTheOneUsedForEncoding()
    {
        $this->expectException(
            SerialCasterException::class,
            'If the decoded serial has a different char list than the provided one'
        );
        SerialCaster::decode(
            SerialCaster::encode(14776335, self::SEED, 26, '01'),
            self::SEED,
            self::ALPHANUMERIC
        );
    }

    public function testBreakIfLengthIsNotHighEnough()
    {
        $this->expectException(SerialCasterException::class);
        // This should break
        SerialCaster::encode(14776336, self::SEED, self::LENGTH, self::ALPHANUMERIC);
    }

    public function testBreakIfCharListIsTooShort()
    {
        $this->expectException(SerialCasterException::class);
        // This should break
        SerialCaster::encode(14776336, self::SEED, self::LENGTH, '1');
    }

    public function testEncodeAndDecodeWithRandomValues()
    {
        srand();
        $loops = rand(50, 70);
        for ($i = 0; $i <= $loops; $i++) {
            srand();
            $randomInteger = rand(0, 999999);
            $this->assertEquals(
                $randomInteger,
                SerialCaster::decode(
                    SerialCaster::encode($randomInteger, self::SEED, self::LENGTH, self::ALPHANUMERIC),
                    self::SEED,
                    self::ALPHANUMERIC
                )
            );
        }
    }

    public function testSpeedTestOnCouponGeneration()
    {
        $acceptableTimeInSeconds = 4;
        $numberOfCoupons = 99999;
        $coupons = [];
        $start = hrtime(true);
        for ($i = 0; $i <= $numberOfCoupons; $i++) {
            srand();
            $randomInteger = rand(0, $numberOfCoupons);
            $coupons[] = SerialCaster::encode($randomInteger, self::SEED, self::LENGTH, self::ALPHANUMERIC);
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

    public function testSpeedTestOnCouponDecode()
    {
        $acceptableTimeInSeconds = 4;
        $numberOfCoupons = 99999;
        $coupons = [];
        $decodedCoupons = [];
        for ($i = 1; $i <= $numberOfCoupons; $i++) {
            srand();
            $randomInteger = rand(1, $numberOfCoupons);
            $coupons[] = SerialCaster::encode($randomInteger, self::SEED, self::LENGTH, self::ALPHANUMERIC);
        }

        $numberOfCoupons = count($coupons);
        $start = hrtime(true);
        for ($i = 0; $i < $numberOfCoupons; $i++) {
            $decodedCoupons[] = SerialCaster::decode($coupons[$i], self::SEED, self::ALPHANUMERIC);
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
