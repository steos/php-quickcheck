<?php

use \QuickCheck\Generator as Gen;
use \QuickCheck\Property;

return Property::forAll(
    [Gen::asciiStrings()],
    function($x){
        return strlen($x) < 200;
    });
