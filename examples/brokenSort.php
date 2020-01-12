<?php

use \QuickCheck\Generator as Gen;
use \QuickCheck\Test;

function isAscending(array $xs) {
    $last = count($xs) - 1;
    for ($i = 0; $i < $last; ++$i) {
        if ($xs[$i] > $xs[$i + 1]) {
            return false;
        }
    }
    return true;
}

function myBrokenSort(array $xs) {
    return $xs;
}

Test::forAll(
    [Gen::ints()->intoArrays()],
    function(array $xs) {
        return isAscending(myBrokenSort($xs));
    }
);
