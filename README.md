# Serial Int Caster

This Library allows encoding an integer to a serial number and the other way around decode it to retrieve the integer.

This library is compatible with **BCMath** and **GMP** extensions to handle large numbers.

## Unit tests

Unit tests are available:

- \`composer install\`
- \`composer run test\`

## Usage

```bash
composer require kwaadpepper/serial-int-caster
```

### For large numbers (BCMath or GMP)

Use the \`BCMathBaseConverter\` or \`GmpBaseConverter\` to handle numbers that exceed PHP's integer capacity. One of these extensions must be installed.

```php
use Kwaadpepper\\Serial\\SerialCaster;
use Kwaadpepper\\Serial\\SerialCasterBuilder;
use Kwaadpepper\\Serial\\Converters\\BCMathBaseConverter;
use Kwaadpepper\\Serial\\Converters\\GmpBaseConverter;
use Kwaadpepper\\Serial\\Shufflers\\FisherYatesShuffler;

// Using BCMathBaseConverter
$int_to_encode = 9223372036854775807; // PHP_INT_MAX
$seed          = 1492;
$length        = 12;
$chars         = 'ABCDEFabcdef0123456789';

$caster_bcmath = (new SerialCasterBuilder(new BCMathBaseConverter()))
    ->withShuffler(new FisherYatesShuffler())
    ->withChars($chars)
    ->withLength($length)
    ->withSeed($seed)
    ->build();

$encoded_number_bcmath = $caster_bcmath->encode($int_to_encode);

// Prints TRUE
print_r($int_to_encode === $caster_bcmath->decode($encoded_number_bcmath));

// Using GmpBaseConverter
$int_to_encode = 9223372036854775807; // PHP_INT_MAX
$seed          = 1492;
$length        = 12;

$caster_gmp = (new SerialCasterBuilder(new GmpBaseConverter()))
    ->withShuffler(new FisherYatesShuffler())
    ->withChars($chars)
    ->withLength($length)
    ->withSeed($seed)
    ->build();

$encoded_number_gmp = $caster_gmp->encode($int_to_encode);

// Prints TRUE
print_r($int_to_encode === $caster_gmp->decode($encoded_number_gmp));
```

### For small numbers (without BCMath/GMP)

If you are working with numbers that do not exceed PHP's maximum integer value (\`PHP_INT_MAX\`), you can use the \`NativeBaseConverter\`. This is a faster solution because it does not rely on external extensions, but it is limited to initial base conversions of 10 or less.

```php
use Kwaadpepper\\Serial\\SerialCaster;
use Kwaadpepper\\Serial\\SerialCasterBuilder;
use Kwaadpepper\\Serial\\Converters\\NativeBaseConverter;
use Kwaadpepper\\Serial\\Shufflers\\FisherYatesShuffler;

$int_to_encode_native = 15;
$seed                 = 1492;
$length               = 6;
$chars                = '01234ABCDE';

$caster_native = (new SerialCasterBuilder(new NativeBaseConverter()))
    ->withShuffler(new FisherYatesShuffler())
    ->withChars($chars)
    ->withLength($length)
    ->withSeed($seed)
    ->build();

$encoded_number_native = $caster_native->encode($int_to_encode_native);

// Prints TRUE
print_r($int_to_encode_native === $caster_native->decode($encoded_number_native));
```
