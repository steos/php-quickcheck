<?php

declare(strict_types=1);

use QuickCheck\Generator as Gen;
use QuickCheck\Test;

Test::forAll(
    [Gen::asciiStrings()],
    function($s): bool {
        return !is_numeric($s);
    },
    1000
);
