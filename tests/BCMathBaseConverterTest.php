<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial\Tests\Converters;

use Kwaadpepper\Serial\Converters\BCMathBaseConverter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kwaadpepper\Serial\Converters\BCMathBaseConverter
 */
class BCMathBaseConverterTest extends TestCase
{
    /** @var \Kwaadpepper\Serial\Converters\BCMathBaseConverter */
    private $converter;

    /**
     * Cette méthode est appelée avant chaque test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('L\'extension BCMath est requise pour ces tests.');
        }
        $this->converter = new BCMathBaseConverter();
    }

    /**
     * @test
     *
     * Test conversion de divers nombres entre bases.
     *
     * @return void
     */
    public function canPerformStandardConversions(): void
    {
        $base10 = '0123456789';
        $base16 = '0123456789ABCDEF';
        $base2  = '01';

        // Hexadécimal -> Décimal.
        $this->assertSame('255', $this->converter->convert('FF', $base16, $base10));
        // Décimal -> Hexadécimal.
        $this->assertSame('FF', $this->converter->convert('255', $base10, $base16));
        // Binaire -> Décimal.
        $this->assertSame('22', $this->converter->convert('10110', $base2, $base10));
        // Décimal -> Binaire.
        $this->assertSame('10110', $this->converter->convert('22', $base10, $base2));
    }

    /**
     * @test
     *
     * @return void
     */
    public function handlesZeroConversionCorrectly(): void
    {
        $base10     = '0123456789';
        $base2      = '01';
        $customBase = 'abcdef';

        // The conversion of '0' should return the first character of the destination base.
        $this->assertSame('0', $this->converter->convert('0', $base10, $base2));
        $this->assertSame('a', $this->converter->convert('0', $base10, $customBase));
    }

    /**
     * @test
     *
     * @return void
     */
    public function canConvertBetweenNonDecimalBases(): void
    {
        $base16 = '0123456789ABCDEF';
        $base2  = '01';

        // Hex -> Binary.
        // A5 (hex) = 165 (dec) = 10100101 (bin).
        $this->assertSame('10100101', $this->converter->convert('A5', $base16, $base2));
    }

    /**
     * @test
     *
     * @return void
     */
    public function handlesVeryLargeNumbersWithBcMath(): void
    {
        // A very large number in hexadecimal (similar to a SHA-256 hash).
        $largeHex = '115df4f8535b44eff4c34a8de4631a5c249f390d405388c2273e93a652f143d4';

        $base16 = '0123456789abcdef';
        $base62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        // Testing a round-trip conversion. The final result should match the original.
        $convertedToBase62 = $this->converter->convert($largeHex, $base16, $base62);
        $revertedToBase16  = $this->converter->convert($convertedToBase62, $base62, $base16);

        $this->assertSame($largeHex, $revertedToBase16);
    }

    /**
     * @test
     *
     * @return void
     */
    public function preservesCaseDuringConversion(): void
    {
        // A base that contains both uppercase and lowercase characters is essential here.
        $base62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base10 = '0123456789';

        // A mixed-case input string.
        $mixedCaseInput = 'aBc1DeF2gH';

        // 1. Convert the string to base 10.
        $base10Representation = $this->converter->convert($mixedCaseInput, $base62, $base10);

        // 2. Reconvert the result back to base 62.
        $revertedResult = $this->converter->convert($base10Representation, $base10, $base62);

        // 3. The final result should be exactly the same as the original input.
        $this->assertSame(
            $mixedCaseInput,
            $revertedResult,
            'La casse doit être parfaitement conservée après une conversion aller-retour.'
        );
    }
}
