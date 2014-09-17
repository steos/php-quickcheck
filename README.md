# PhpQuickCheck

PhpQuickCheck is a generative testing library for PHP based on
clojure.test.check.

> Don't write tests. Generate them.
> - John Hughes

## Huh?

Generative testing, also called property-based testing, is about
describing the behaviour of your system in terms of properties that
should hold true for all possible input.

### Examples

Here is a failing example:

```php
use QCheck\Generator as Gen;
use QCheck\Quick;

$stringsAreNeverNumeric = Gen::forAll(
    [Gen::asciiStrings()],
    function($str) {
        return !is_numeric($str);
    }
);

$result = Quick::check(1000, $stringsAreNeverNumeric);
var_dump($result);
```

This will run up to 1000 tests trying to falsify the given property. In
this case the property will be falsified eventually so the result will
contain a `"fail"` key which contains the exact values that caused the
property to fail. It will also contain a `"shrunk"` key which itself
contains a `"smallest"` key which contains the shrunk values. In this
case, `"fail"` may contain `["727"]` and `"smallest"` will contain
`["0"]`.

## Project Status

PhpQuickCheck is highly experimental and in its very early stages. Only
the core functionality has been implemented so far. At the moment the
project is mostly still in a proof-of-concept stage so a lot of
essential generators are not implemented yet and there is obviously no
documentation whatsoever.

### Contributing

All suggestions, contributions and flames are welcome.

Feel free to fork and send a pull request. If you intend to make
major changes please get in touch so we can coordinate our efforts.

## Credits

All credit goes to clojure.test.check, this project is mostly just a
port.

## Requirements

Requires PHP 5.5.x with 64 bit integers and gmp extension.

## License

Copyright © 2014 Stefan Oestreicher.

Distributed under the terms of the BSD (3-Clause) license.

