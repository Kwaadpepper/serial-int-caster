<?php

namespace Tests;

use Kwaadpepper\Serial\Converters\BCMathBaseConverter;
use Kwaadpepper\Serial\SerialCaster;
use PHPUnit\Framework\TestCase;

class KotlinComparisonTest extends TestCase
{
    private const ALPHANUMERIC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    private const LENGTH       = 10;
    private const MAX_INTEGER  = 99999999999;

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
     * Test kotlin generated serials
     *
     * @return void
     * @throws \RuntimeException If reading the file fails.
     */
    public function testKotlinGeneratedFile()
    {
        $kotlinFileName = 'serialTestFileKotlin.csv';
        if (!\file_exists($kotlinFileName)) {
            $this->markTestSkipped("Missing kotlin file `{$kotlinFileName}`, this test will be skipped");
        }
        if (!($file = \fopen($kotlinFileName, 'r'))) {
            throw new \RuntimeException("Could not read `{$kotlinFileName}` file");
        }
        // * Read CSV
        $cvsContent = [];
        while (!empty($line = \fgetcsv($file, 0, ';'))) {
            if (!empty($line[0])) {
                $cvsContent[] = $line;
            }
        }

        $header   = ['seed', 'length', 'dict'];
        $suheader = ['integer', 'encoded'];

        // * Check Header
        $this->assertTrue(
            empty(\array_diff($cvsContent[0], $header)),
            'Array structure is invalid expected ' . \json_encode($header)
        );

        // * Check Header values
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

        // * Check subheader
        $this->assertTrue(
            empty(\array_diff($cvsContent[2], $suheader)),
            'Array structure is invalid expected ' . \json_encode($suheader)
        );

        // * Check rows
        $size = \count($cvsContent);
        $this->assertTrue($size - 3 > 0, 'There should be line to check in the file');

        for ($i = 3; $i < $size; $i++) {
            $this->assertIsArray($cvsContent[$i], 'The row should be an array');
            $this->assertArrayHasKey(0, $cvsContent[$i], 'The row should have an integer');
            $this->assertTrue(\is_numeric($cvsContent[$i][0]), 'The row should have an integer');
            $this->assertArrayHasKey(1, $cvsContent[$i], 'The row should have a serial');
            $this->assertTrue(\is_string($cvsContent[$i][1]), 'The row should have a serial');
        }
        // * Run encode and decode tests
        $seed = \intval($cvsContent[1][0]);
        for ($i = 3; $i < $size; $i++) {
            $integer = \intval($cvsContent[$i][0]);
            $serial  = \strval($cvsContent[$i][1]);

            $this->assertEquals($serial, $testEncode = $this->caster->encode(
                $integer,
                $seed,
                self::LENGTH,
            ), "Encode `{$integer}` should give `{$serial}`, got `{$testEncode}`");
            $this->assertEquals("$integer", $testDecode = $this->caster->decode(
                $serial,
                $seed,
            ), "Decode `{$serial}` should give `{$integer}`, got `{$testDecode}`");
        }
        \fclose($file);
    }

    /**
     * Generate test list for Kotlin usage
     *
     * @param integer $lines The number of test lines to print.
     * @return void
     * @throws \RuntimeException If the argument 'lines' is missing or not above 0.
     */
    public static function generateKotlinTestList(int $lines = 0)
    {
        $lines_to_genenerate = $lines;

        if ($lines_to_genenerate < 1) {
            throw new \RuntimeException(\sprintf(
                '%s::%s requires an argument \'lines\' with a positive number above 0.',
                __CLASS__,
                __FUNCTION__
            ));
        }

        $caster = new SerialCaster(
            new BCMathBaseConverter(),
            self::ALPHANUMERIC
        );

        $seed          = \intval(microtime(true) * 1000000);
        $csv_rows      = [
            ['seed', 'length', 'dict'],
            [$seed, self::LENGTH, self::ALPHANUMERIC],
            ['integer', 'encoded']
        ];
        $header_offset = \count($csv_rows);

        // * Since Serial Caster also use mt_rand we will first inject our integer list.
        // * Generate list of integers (usage unaltered of mt_rand).
        $i = 1;
        \mt_srand($seed);
        while ($i++ <= $lines_to_genenerate) {
            $integer    = \mt_rand(1, self::MAX_INTEGER);
            $csv_rows[] = [$integer];
        }

        // * Insert encoded column (usage unaltered of mt_rand).
        $i = $header_offset;
        while ($i < ($lines_to_genenerate + $header_offset)) {
            /** @var integer $seed Our previous seed integer. */
            $integer          = $csv_rows[$i][0];
            $csv_rows[$i++][] = $caster->encode($integer, $seed, self::LENGTH);
        }

        // * Print into file.
        $filename = 'serialTestFilePhp.csv';
        if (!($file = \fopen($filename, 'w'))) {
            throw new \RuntimeException("Failed to open file `{$filename}` for writing");
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
     * @throws \RuntimeException If writing to file fails.
     */
    private static function writeToFile(string $filename, $pointer, string $input): void
    {
        if (!\fputs($pointer, $input)) {
            throw new \RuntimeException("Failed to write to file `{$filename}` for writing");
        }
    }
}
