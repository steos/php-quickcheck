<?php

use \QuickCheck\Generator as Gen;
use \QuickCheck\Test;

Test::forAll(
    [Gen::asciiStrings()],
    function($s) {
        return !is_numeric($s);
    },
    1000
);
