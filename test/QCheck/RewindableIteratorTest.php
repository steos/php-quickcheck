<?php

namespace QCheck;

use QCheck\FP;
use QCheck\RewindableIterator;

class RewindableIteratorTest extends \PHPUnit_Framework_TestCase {
    function testRewindableIterator() {
        $range = new RewindableIterator(FP::range(0, 3));

        $range->rewind();
        $this->assertTrue($range->valid());
        $this->assertEquals(0, $range->current());

        $range->next();
        $this->assertTrue($range->valid());
        $this->assertEquals(1, $range->current());

        $range->next();
        $this->assertTrue($range->valid());
        $this->assertEquals(2, $range->current());

        $range->next();
        $this->assertFalse($range->valid());

        $range->rewind();
        $this->assertTrue($range->valid());
        $this->assertEquals(0, $range->current());
    }

    function testRewindBeforeGeneratorIsConsumed() {
        $range = new RewindableIterator(FP::range(0, 3));

        $range->rewind();
        $this->assertTrue($range->valid());
        $this->assertEquals(0, $range->current());

        $range->next();
        $this->assertTrue($range->valid());
        $this->assertEquals(1, $range->current());

        $range->rewind();
        $this->assertTrue($range->valid());
        $this->assertEquals(0, $range->current());

        $range->next();
        $this->assertTrue($range->valid());
        $this->assertEquals(1, $range->current());

        $range->next();
        $this->assertTrue($range->valid());
        $this->assertEquals(2, $range->current());

        $range->next();
        $this->assertFalse($range->valid());

        $range->rewind();
        $this->assertTrue($range->valid());
        $this->assertEquals(0, $range->current());
    }
}
