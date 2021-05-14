<?php

namespace Tests;

use Kwaadpepper\Serial\Exceptions\SerialCasterException;
use Kwaadpepper\Serial\SerialCaster;
use PHPUnit\Framework\TestCase;

class SerialCasterTest extends TestCase
{
    private const ALPHANUMERIC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    public function __construct()
    {
        require_once __DIR__ . "/../vendor/larapack/dd/src/helper.php";
        parent::__construct();
    }

    public function testSerialEncode()
    {
        $this->assertEquals(
            SerialCaster::encode(14776335, 6, self::ALPHANUMERIC),
            '1bzzzO',
            //  phpcs:ignore Generic.Files.LineLength.TooLong
            'Encoding 14776335(10) on base with ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 should give 1bzzzO'
        );
    }

    public function testSerialDecode()
    {
        $this->assertEquals(SerialCaster::decode('000HLC', self::ALPHANUMERIC), 666);
    }

    public function testBreakIfSerialHasInvalidChar()
    {
        $this->expectException(
            SerialCasterException::class,
            'If serial has an invalid char it should throw an exception'
        );
        SerialCaster::decode('*', self::ALPHANUMERIC);
    }

    public function testBreakIfDecodedIsTooShort()
    {
        $this->expectException(
            SerialCasterException::class,
            'If the decoded serial is too short (<=2) it should throw an exception'
        );
        SerialCaster::decode('A', self::ALPHANUMERIC);
    }

    public function testBreakIfDecodedCharListIsDifferentThanTheOneUsedForEncoding()
    {
        $this->expectException(
            SerialCasterException::class,
            'If the decoded serial has a different char list than the provided one'
        );
        SerialCaster::decode(SerialCaster::encode(14776335, 26, '01'), self::ALPHANUMERIC);
    }

    public function testBreakIfLengthIsNotHighEnough()
    {
        $this->expectException(SerialCasterException::class);
        // This should break
        SerialCaster::encode(14776336, 6, self::ALPHANUMERIC);
    }

    public function testBreakIfCharListIsTooShort()
    {
        $this->expectException(SerialCasterException::class);
        // This should break
        SerialCaster::encode(14776336, 6, '1');
    }

    public function testEncodeAndDecodeWithRandomValues()
    {
        $loops = rand(50, 70);
        for ($i = 0; $i <= $loops; $i++) {
            $randomInteger = rand(0, 9999999);
            $this->assertEquals(
                $randomInteger,
                SerialCaster::decode(
                    SerialCaster::encode($randomInteger, 6, self::ALPHANUMERIC),
                    self::ALPHANUMERIC
                )
            );
        }
    }

    public function testSpeedTestOnCouponGeneration()
    {
        $acceptableTimeInSeconds = 2;
        $numberOfCoupons = 99999;
        $coupons = [];
        $start = hrtime(true);
        for ($i = 0; $i <= $numberOfCoupons; $i++) {
            $coupons[] = SerialCaster::encode($i, 7, self::ALPHANUMERIC);
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
        $acceptableTimeInSeconds = 3;
        $numberOfCoupons = 99999;
        $coupons = [];
        $decodedCoupons = [];
        for ($i = 1; $i <= $numberOfCoupons; $i++) {
            $coupons[] = SerialCaster::encode($i, 7, self::ALPHANUMERIC);
        }

        $numberOfCoupons = count($coupons);
        $start = hrtime(true);
        for ($i = 0; $i < $numberOfCoupons; $i++) {
            $decodedCoupons[] = SerialCaster::decode($coupons[$i], self::ALPHANUMERIC);
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
