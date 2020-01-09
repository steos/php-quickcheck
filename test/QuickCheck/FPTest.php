<?php

namespace QuickCheck;

use PHPUnit\Framework\TestCase;

class FPTest extends TestCase {
    function testTakeNth() {
        $this->assertEquals(
            [0, 2, 4],
            FP::realize(FP::takeNth(2, FP::range(0, 5)))
        );
        $this->assertEquals(
            [0, 3, 6],
            FP::realize(FP::takeNth(3, FP::range(0, 8)))
        );
    }

    function testPartition() {
        $this->assertEquals(
            [[0], [1], [2]],
            FP::realize(FP::partition(1, FP::range(0, 3)))
        );
        $this->assertEquals(
            [[0, 1], [2, 3], [4, 5], [6]],
            FP::realize(FP::partition(2, FP::range(0, 7)))
        );
        $this->assertEquals(
            [[0, 1, 2], [3, 4, 5], [6, 7]],
            FP::realize(FP::partition(3, FP::range(0, 8)))
        );
    }
}