# serial-int-caster

This Library allows to encode an integer to a serial number and the other way around decode it to retrieve the integer.

## Unit tests

 Unit tests are available :
 - `composer install`
 - `composer run test`

## Usage
```composer install  kwaadpepper/serial-int-caster ```

```SerialCaster::encode(15, 6, 'ABCDEFabcdef0123456789')```

```SerialCaster::decode(15, 'ABCDEFabcdef0123456789')```
