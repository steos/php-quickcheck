# PHPQuickCheck

[![Build Status](https://travis-ci.org/steos/php-quickcheck.svg?branch=master)](https://travis-ci.org/steos/php-quickcheck)

PHPQuickCheck is a generative testing library for PHP based on
clojure.test.check.

> Don't write tests. Generate them. - John Hughes

## Huh?

Generative testing, also called property-based testing, is about
describing the behaviour of your system in terms of properties that
should hold true for all possible input.

### Example

> Please note that this example and documentation refers to unreleased and unstable API.
> For the documentation of the latest released stable API refer to the [v1.0.0 release tree](https://github.com/steos/php-quickcheck/tree/v1.0.0)

```php
use QuickCheck\Generator as Gen;
use QuickCheck\Property;

$stringsAreNeverNumeric = Property::forAll(
    [Gen::asciiStrings()],
    function($str) {
        return !is_numeric($str);
    }
);

$result = $stringsAreNeverNumeric->check(1000);
var_dump($result);
```

This will produce something like the following output (json encoded for readability):

```
"result": false,
"seed": 1411306705536,
"failing_size": 109,
"num_tests": 110,
"fail": [
    "727"
],
"shrunk": {
    "nodes_visited": 15,
    "depth": 4,
    "result": false,
    "smallest": [
        "0"
    ]
}
```

### Documentation

- [Introduction](doc/introduction.md)
- [PHPUnit Support](doc/phpunit.md)
- [Using Annotations](doc/annotations.md)
- [Generator Examples](doc/generators.md)

#### Other Resources

- [A QuickCheck Primer for PHP Developers](https://medium.com/@thinkfunctional/a-quickcheck-primer-for-php-developers-5ffbe20c16c8)
- [Testing the hard stuff and staying sane (John Hughes)](https://www.youtube.com/watch?v=zi0rHwfiX1Q)

### Installation

PHPQuickCheck is available via Packagist so you can require it with composer:

```
$ composer require steos/quickcheck
```

### xdebug

PHPQuickCheck uses a lot of functional programming techniques which leads to a lot of nested functions.
With xdebug default settings it can quickly lead to this error:

```
Error: Maximum function nesting level of '256' reached, aborting!
```

This happens due to the infinite recursion protection setting `xdebug.max_nesting_level`.
Best is to disable this or set it to a high value.
The phpunit config sets it to `9999`.

### Performance

- Disable xdebug to get tests to run faster. It has a huge impact on the runtime performance.

- Use the GMP extension. The RNG will use the gmp functions if available. Otherwise it falls back to very slow bit-fiddling in php userland.

## Project Status

PHPQuickCheck is somewhat experimental. The core functionality of clojure.test.check (as of March 2016) has been implemented.

### Contributing

All contributions are welcome.

Feel free to fork and send a pull request. If you intend to make
major changes please get in touch so we can coordinate our efforts.

#### Dev Setup

The repository contains a Dockerfile to quickly set up a dev environment.
It is based on the `php:7.4.1-cli` image and adds xdebug, gmp and composer.

```
$ docker build -t php-quickcheck-dev dev-env
$ docker run --rm -it --mount src=$(pwd),target=/quickcheck,type=bind php-quickcheck-dev bash
# cd /quickcheck
# composer install
# vendor/bin/phpunit
```

The image also contains a small script `toggle-ext` to toggle php extensions on and off:

```
root@c871096e2c92:/quickcheck# toggle-ext xdebug
xdebug is now disabled
root@c871096e2c92:/quickcheck#
```

## Credits

All credit goes to clojure.test.check, this project is mostly just a port.

## Requirements

Requires PHP 7.3.x with 64 bit integers. The gmp extension is recommended but not required.

## License

Copyright © 2020, Stefan Oestreicher and contributors.

Distributed under the terms of the BSD (3-Clause) license.
