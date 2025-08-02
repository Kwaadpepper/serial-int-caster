<?php

declare(strict_types=1);

namespace Tests;

use Kwaadpepper\Serial\Converters\NativeBaseConverter;
use PHPUnit\Framework\TestCase;

final class NativeBaseConverterTest extends TestCase
{
    /** @var NativeBaseConverter */
    private $converter;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new NativeBaseConverter();
    }

    /**
     * @dataProvider provideConversionData
     * @covers \Kwaadpepper\Serial\Converters\NativeBaseConverter::convert
     *
     * Test conversion of various numbers between bases.
     *
     * @param string $number   The number to convert.
     * @param string $fromBase The base to convert from.
     * @param string $toBase   The base to convert to.
     * @param string $expected The expected result of the conversion.
     * @return void
     */
    public function testConvert(string $number, string $fromBase, string $toBase, string $expected): void
    {
        $result = $this->converter->convert($number, $fromBase, $toBase);
        $this->assertSame($expected, $result);
    }

    /**
     * @covers \Kwaadpepper\Serial\Converters\NativeBaseConverter::convert
     *
     * Test that conversion throws an exception for unsupported bases.
     *
     * @return void
     */
    public function testConvertThrowsExceptionOnHighBase(): void
    {
        $this->expectException(\Error::class);

        $this->expectExceptionMessage(
            'NativeConverter ne supporte pas la conversion à partir de bases supérieures à 10,
                il faut utiliser l\'extension bcmath ou gmp.'
        );

        $base62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base10 = '0123456789';

        $this->converter->convert('3D7', $base62, $base10);
    }

    /**
     * Data provider for conversion tests.
     *
     * @return array[]
     */
    public function provideConversionData(): array
    {
        $base8  = '01234567';
        $base10 = '0123456789';
        $base2  = '01';

        return [
            'base10_to_base10_no_change' => ['987654321', $base10, $base10, '987654321'],
            'base10_to_base2' => ['255', $base10, $base2, '11111111'],
            'base2_to_base10' => ['11111111', $base2, $base10, '255'],
            'base10_to_base8' => ['12345', $base10, $base8, '30071'],
            'base8_to_base10' => ['30071', $base8, $base10, '12345'],
        ];
    }
}
