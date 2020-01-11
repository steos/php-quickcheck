<?php

use \QuickCheck\Generator as Gen;
use \QuickCheck\Property;

return Property::forAll(
    [Gen::ints()->intoArrays()],
    function($xs) {
        $n = count(array_filter($xs, function($n) {
            return $n > 1000;
        }));
        return $n === 0;
    });
