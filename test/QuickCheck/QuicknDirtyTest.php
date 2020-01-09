<?php

namespace QuickCheck;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use QuickCheck\Generator as Gen;
use QuickCheck\PHPUnit\PropertyConstraint;

class QuicknDirtyTest extends TestCase {
    /**
     * @dataProvider ints
     */
    function testInt($int) {
        $this->assertTrue(is_integer($int));
    }
    function ints() {
        return Gen::tuples(Gen::ints())->takeSamples();
    }

    /**
     * @dataProvider lists
     */
    function testStringList($list) {
        $this->assertTrue(is_array($list));
        $this->assertContainsOnly('int', array_keys($list));
        $this->assertContainsOnly('string', $list);
    }
    function lists() {
        return Gen::tuples(Gen::asciiStrings()->intoArrays())->takeSamples();
    }

    function testQuickCheckShrink() {
        $result = Property::forAll([Gen::asciiStrings()], function($s) {
            return !is_numeric($s);
        })->check(10000);
        $this->assertFalse($result['result']);
        $smallest = $result['shrunk']['smallest'][0];
        $this->assertEquals('0', $smallest,
            "expected smallest to be '0' but got '$smallest'");
    }

    function testPropConstraintFailure() {
        $this->expectException(AssertionFailedError::class);
        $this->assertThat(Property::forAll([Gen::strings()], function($str) {
            return strlen($str) < 10;
        }), PropertyConstraint::check(100));
    }
}
