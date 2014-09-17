<?php

namespace QCheck;

use QCheck\Generator as Gen;
use QCheck\Quick;
use QCheck\FP;

class QuicknDirtyTest extends \PHPUnit_Framework_TestCase {
    /**
     * @dataProvider ints
     */
    function testInt($int) {
        $this->assertTrue(is_integer($int));
    }
    function ints() {
        return FP::take(50, Gen::tuples(Gen::ints())->samples());
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
        return FP::take(20, Gen::tuples(Gen::lists(Gen::asciiStrings()))->samples());
    }

    function testQuickCheckShrink() {
        $prop = Gen::forAll([Gen::asciiStrings()], function($s) {
            return !is_numeric($s);
        });
        $result = Quick::check(1000, $prop);
        $this->assertFalse($result['result']);
        $smallest = $result['shrunk']['smallest'][0];
        $this->assertEquals('0', $smallest,
            "expected smallest to be '0' but got '$smallest'");
    }
}
