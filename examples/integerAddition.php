<?php

use QuickCheck\Generator as Gen;
use QuickCheck\Test;

Test::check('is commutative')
    ->times(1000)
    ->maxSize(500)
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

Test::check('is distributive')
    ->times(1000)
    ->maxSize(1337)
    ->forAll(
        [Gen::ints(), Gen::ints(), Gen::ints()],
        function($a, $b, $c) {
            return ($a + $b) + $c == $a + ($b + $c);
        });
