<?php

declare(strict_types=1);

use QuickCheck\Generator as Gen;
use QuickCheck\Test;

Test::check('is commutative')
    ->times(1000)
    ->maxSize(500)
    ->forAll(
        [Gen::ints(), Gen::ints()],
        fn($a, $b): bool => $a + $b === $b + $a
    );

Test::check('has zero as identity')
    ->forAll(
        [Gen::ints()],
        fn($x): bool => $x + 0 === $x
    );

Test::check('is associative')
    ->times(1000)
    ->maxSize(1337)
    ->forAll(
        [Gen::ints(), Gen::ints(), Gen::ints()],
        fn($a, $b, $c): bool => ($a + $b) + $c == $a + ($b + $c)
    );
