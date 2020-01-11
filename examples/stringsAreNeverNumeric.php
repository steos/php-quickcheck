<?php

use \QuickCheck\Generator as Gen;
use \QuickCheck\Property;

return Property::forAll(
    [Gen::strings()],
    function($s) {
        return !is_numeric($s);
    });
