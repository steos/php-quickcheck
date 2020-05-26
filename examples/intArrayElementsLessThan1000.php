<?php

declare(strict_types=1);

use QuickCheck\Generator as Gen;
use QuickCheck\Test;

Test::forAll(
    [Gen::ints()->intoArrays()],
    function (array $xs): bool {
        $n = count(array_filter($xs, fn(int $n): bool => $n > 1000));
        return $n === 0;
    });
