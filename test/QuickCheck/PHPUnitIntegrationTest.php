<?php

namespace QuickCheck;

use PHPUnit\Framework\TestCase;
use QuickCheck\Generator as Gen;
use QuickCheck\PHPUnit\PropertyConstraint;

class PHPUnitIntegrationTest extends TestCase {
    /**
     * Test example from PHPUnit integration documentation
     */
    public function testStringsAreLessThanTenChars()
    {
        $property = Property::forAll(
            [Gen::strings()],
            fn ($s): bool => 10 > strlen($s)
        );

        $this->assertFalse(
            PropertyConstraint::check(50)->evaluate(
                $property,
                'Expected property to fail',
                true
            )
        );
    }
}