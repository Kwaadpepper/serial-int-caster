# Serial Int Caster

This Library allows to encode an integer to a serial number and the other way around decode it to retrieve the integer.

## Unit tests

 Unit tests are available :

- `composer install`
- `composer run test`

 Generate Kotlin test file
 `composer run generateList -- --lines=9999`
 To generate csv file to the kotlin unit tests put this file in its root folder and run unit tests.
 <https://github.com/Kwaadpepper/serial-int-caster-kotlin>

## Usage

``` Bash
composer install  kwaadpepper/serial-int-caster
```

``` PHP
$int_to_encode = 15;
$dictionnary = 'ABCDEFabcdef0123456789';
$seed = 1492;
/** @var string $encoded_number ('1bzzzO') */
$encoded_number = SerialCaster::encode(number: $int_to_encode, seed: $seed, length: 6, chars: $dictionnary);

/** @var integer $decoded_number (15) */
$decoded_number = SerialCaster::decode(serial: '1bzzzO', seed: $seed, chars: $dictionnary);

/** Prints TRUE */
print_r($int_to_encode === $decoded_number);
```
