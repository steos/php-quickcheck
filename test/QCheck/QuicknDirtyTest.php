<?php

namespace QCheck;

use PHPUnit\Framework\TestCase;
use QCheck\Generator as Gen;

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
        $prop = Gen::forAll([Gen::asciiStrings()], function($s) {
            return !is_numeric($s);
        });
        $result = Quick::check(10000, $prop);
        $this->assertFalse($result['result']);
        $smallest = $result['shrunk']['smallest'][0];
        $this->assertEquals('0', $smallest,
            "expected smallest to be '0' but got '$smallest'");
    }
}
