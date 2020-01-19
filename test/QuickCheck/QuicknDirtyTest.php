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
        $this->assertIsArray($list);
        $this->assertContainsOnly('int', array_keys($list));
        $this->assertContainsOnly('string', $list);
    }
    function lists() {
        return Gen::tuples(Gen::asciiStrings()->intoArrays())->takeSamples();
    }

    /**
     * @dataProvider minMaxArrays
     */
    function testMinMaxArrays($xs) {
        $this->assertIsArray($xs);
        $this->assertGreaterThanOrEqual(23, count($xs));
        $this->assertLessThanOrEqual(42, count($xs));
    }
    function minMaxArrays() {
        return Gen::tuples(Gen::ints()->intoArrays(23, 42))->takeSamples(50);
    }

    /**
     * @dataProvider fixedLengthArrays
     */
    function testFixedLengthArrays($xs) {
        $this->assertIsArray($xs);
        $this->assertCount(23, $xs);
    }
    function fixedLengthArrays() {
        return Gen::tuples(Gen::ints()->intoArrays(23))->takeSamples(50);
    }

    function testQuickCheckShrink() {
        $p = Property::forAll([Gen::asciiStrings()], function($s) {
            return !is_numeric($s);
        });

        $result = Property::check($p, 10000);
        $this->assertNotTrue($result, 'property was not falsified');

        $smallest = $result->shrunk()->test()->arguments()[0];
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
