<?php

namespace QuickCheck;

use PHPUnit\Framework\TestCase;

class FPTest extends TestCase {
    function testTakeNth() {
        $this->assertEquals(
            [0, 2, 4],
            Lazy::realize(Lazy::takeNth(2, Lazy::range(0, 5)))
        );
        $this->assertEquals(
            [0, 3, 6],
            Lazy::realize(Lazy::takeNth(3, Lazy::range(0, 8)))
        );
    }

    function testPartition() {
        $this->assertEquals(
            [[0], [1], [2]],
            Lazy::realize(Lazy::partition(1, Lazy::range(0, 3)))
        );
        $this->assertEquals(
            [[0, 1], [2, 3], [4, 5], [6]],
            Lazy::realize(Lazy::partition(2, Lazy::range(0, 7)))
        );
        $this->assertEquals(
            [[0, 1, 2], [3, 4, 5], [6, 7]],
            Lazy::realize(Lazy::partition(3, Lazy::range(0, 8)))
        );
    }
}