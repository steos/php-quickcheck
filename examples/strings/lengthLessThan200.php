<?php

use \QuickCheck\Generator as Gen;
use \QuickCheck\Test;

Test::forAll(
    [Gen::asciiStrings()],
    function($x){
        return strlen($x) < 200;
    },
    1000,
    400
);
