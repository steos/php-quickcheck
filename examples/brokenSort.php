<?php

declare(strict_types=1);

use QuickCheck\Generator as Gen;
use QuickCheck\Test;

function isAscending(array $xs): bool {
    $last = count($xs) - 1;
    for ($i = 0; $i < $last; ++$i) {
        if ($xs[$i] > $xs[$i + 1]) {
            return false;
        }
    }
    return true;
}

function myBrokenSort(array $xs): array {
    return $xs;
}

Test::forAll(
    [Gen::ints()->intoArrays()],
    function(array $xs): bool {
        return isAscending(myBrokenSort($xs));
    }
);
