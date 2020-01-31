# Writing CLI Tests

You can write tests that can be run by the CLI by using the `QuickCheck\Test` functions.
This class is only meant to be used to set up tests that will be run by the CLI.
To run tests programmatically use the [`QuickCheck\Property`](./introduction.md) API.

## `Test::forAll`

```php
static Test::forAll ( array $generators, callable $predicate [, int $numTests = 100 [, int $maxSize = 200 ]] ) : void
```

A single test per file can be defined with `QuickCheck\Test::forAll`.

```php
use QuickCheck\Generator as Gen;
use QuickCheck\Test;

Test::forAll(
    [Gen::asciiStrings()],
    function($s) {
        return !is_numeric($s);
    },
    1000
);
```

## `Test::check`

```php
static Test::check ([ string $name = null ]) : Check
```

You can write multiple tests per file by using the `QuickCheck\Test::check` function.
It returns a `QuickCheck\Check` instance.

### `QuickCheck\Check`

The `Check` instance is a simple builder that allows you to fluently configure the test:

```php
Check::times ( int $n ) : Check
Check::maxSize ( int $n ) : Check
Check::numTests ( int $n ) : Check
Check::forAll ( array $generators, callable $predicate ) : void
```

Note that `Check::forAll` will register the test in the suite and thus returns `void`.

### Examples

```php
use QuickCheck\Generator as Gen;
use QuickCheck\Test;

Test::check('is commutative')
    ->forAll(
        [Gen::ints(), Gen::ints()],
        function($a, $b) {
            return $a + $b === $b + $a;
        });

Test::check('has zero as identity')
    ->forAll(
        [Gen::ints()],
        function($x) {
            return $x + 0 === $x;
        });

Test::check('is associative')
    ->times(1000)
    ->maxSize(1337)
    ->forAll(
        [Gen::ints(), Gen::ints(), Gen::ints()],
        function($a, $b, $c) {
            return ($a + $b) + $c == $a + ($b + $c);
        });

```
