<?php

namespace Tests;

use Kwaadpepper\Serial\Converters\BCMathBaseConverter;
use Kwaadpepper\Serial\SerialCasterBuilder;
use Kwaadpepper\Serial\Shufflers\FisherYatesShuffler;
use PHPUnit\Framework\TestCase;

class KotlinComparisonTest extends TestCase
{
    private const ALPHANUMERIC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    private const LENGTH       = 10;
    private const MAX_INTEGER  = 99999999999;

    /**
     * Test kotlin generated serials
     *
     * @return void
     * @throws \Tests\TestException If reading the file fails.
     */
    public function testKotlinGeneratedFile()
    {
        // * GIVEN
        $kotlinFileName = 'serialTestFileKotlin.csv';
        if (!\file_exists($kotlinFileName)) {
            $this->markTestSkipped("Missing kotlin file `{$kotlinFileName}`, this test will be skipped");
        }
        if (!($file = \fopen($kotlinFileName, 'r'))) {
            throw new TestException("Could not read `{$kotlinFileName}` file");
        }

        $cvsContent = [];
        while (!empty($line = \fgetcsv($file, 0, ';'))) {
            if (!empty($line[0])) {
                $cvsContent[] = $line;
            }
        }
        \fclose($file);

        $header   = ['seed', 'length', 'dict'];
        $suheader = ['integer', 'encoded'];

        $this->assertTrue(
            empty(\array_diff($cvsContent[0], $header)),
            'Array structure is invalid expected ' . \json_encode($header)
        );
        $this->assertTrue(
            \is_array($cvsContent[1]) &&
                \count($cvsContent[1]) === 3 &&
                \is_numeric($cvsContent[1][0]) &&
                \intval($cvsContent[1][1]) === self::LENGTH &&
                $cvsContent[1][2] === self::ALPHANUMERIC,
            'Array Header values should be like ' . \json_encode([
                'integer value', self::LENGTH, self::ALPHANUMERIC
            ])
        );
        $this->assertTrue(
            empty(\array_diff($cvsContent[2], $suheader)),
            'Array structure is invalid expected ' . \json_encode($suheader)
        );

        $size = \count($cvsContent);
        $this->assertTrue($size - 3 > 0, 'There should be line to check in the file');

        for ($i = 3; $i < $size; $i++) {
            $this->assertIsArray($cvsContent[$i], 'The row should be an array');
            $this->assertArrayHasKey(0, $cvsContent[$i], 'The row should have an integer');
            $this->assertTrue(\is_numeric($cvsContent[$i][0]), 'The row should have an integer');
            $this->assertArrayHasKey(1, $cvsContent[$i], 'The row should have a serial');
            $this->assertTrue(\is_string($cvsContent[$i][1]), 'The row should have a serial');
        }

        // * WHEN
        $seed = \intval($cvsContent[1][0]);

        $caster = (new SerialCasterBuilder(new BCMathBaseConverter()))
            ->withShuffler(new FisherYatesShuffler())
            ->withChars(self::ALPHANUMERIC)
            ->withLength(self::LENGTH)
            ->withSeed($seed)
            ->build();

        // * THEN
        for ($i = 3; $i < $size; $i++) {
            $integer = \intval($cvsContent[$i][0]);
            $serial  = \strval($cvsContent[$i][1]);

            $this->assertEquals(
                $serial,
                $testEncode = $caster->encode($integer),
                "Encode `{$integer}` should give `{$serial}`, got `{$testEncode}`"
            );
            $this->assertEquals(
                "$integer",
                $testDecode = $caster->decode($serial),
                "Decode `{$serial}` should give `{$integer}`, got `{$testDecode}`"
            );
        }
    }

    /**
     * Generate test list for Kotlin usage
     *
     * @param integer $lines The number of test lines to print.
     * @return void
     * @throws \Tests\TestException If the argument 'lines' is missing or not above 0.
     */
    public static function generateKotlinTestList(int $lines = 0)
    {
        // * GIVEN
        $lines_to_genenerate = $lines;
        if ($lines_to_genenerate < 1) {
            throw new TestException(\sprintf(
                '%s::%s requires an argument \'lines\' with a positive number above 0.',
                __CLASS__,
                __FUNCTION__
            ));
        }

        $seed   = \intval(microtime(true) * 1000000);
        $caster = (new SerialCasterBuilder(new BCMathBaseConverter()))
            ->withShuffler(new FisherYatesShuffler())
            ->withChars(self::ALPHANUMERIC)
            ->withLength(self::LENGTH)
            ->withSeed($seed)
            ->build();

        $csv_rows = [
            ['seed', 'length', 'dict'],
            [$seed, self::LENGTH, self::ALPHANUMERIC],
            ['integer', 'encoded']
        ];

        // * WHEN
        \mt_srand($seed);
        for ($i = 0; $i < $lines_to_genenerate; $i++) {
            $integer    = \mt_rand(1, self::MAX_INTEGER);
            $csv_rows[] = [$integer, $caster->encode($integer)];
        }

        // * THEN
        $filename = 'serialTestFilePhp.csv';
        if (!($file = \fopen($filename, 'w'))) {
            throw new TestException("Failed to open file `{$filename}` for writing");
        }

        foreach ($csv_rows as $csv_row) {
            self::writeToFile($filename, $file, \implode(';', $csv_row) . "\n");
        }
        self::writeToFile($filename, $file, "\n");
        \fclose($file);
    }

    /**
     * Write a line to file.
     *
     * @param string   $filename
     * @param resource $pointer
     * @param string   $input
     * @return void
     * @throws \Tests\TestException If writing to file fails.
     */
    private static function writeToFile(string $filename, $pointer, string $input): void
    {
        if (!\fputs($pointer, $input)) {
            throw new TestException("Failed to write to file `{$filename}` for writing");
        }
    }
}
