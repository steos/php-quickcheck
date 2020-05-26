<?php

declare(strict_types=1);

use QuickCheck\Generator as Gen;
use QuickCheck\Test;

Test::forAll(
    [Gen::asciiStrings()],
    function($x): bool {
        return strlen($x) < 200;
    },
    1000,
    400
);
