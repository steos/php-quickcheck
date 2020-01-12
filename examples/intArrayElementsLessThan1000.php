<?php

use \QuickCheck\Generator as Gen;
use \QuickCheck\Test;

Test::forAll(
    [Gen::ints()->intoArrays()],
    function($xs) {
        $n = count(array_filter($xs, function($n) {
            return $n > 1000;
        }));
        return $n === 0;
    });
