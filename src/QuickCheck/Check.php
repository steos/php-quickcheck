<?php

namespace QuickCheck;

class Check
{
    private $numTests = 100;
    private $maxSize = 200;
    private $name;
    private $suite;
    private $property;

    function __construct(CheckSuite $suite, string $name = null) {
        $this->name = $name;
        $this->suite = $suite;
    }

    function forAll(array $args, callable $pred) {
        $this->property = Property::forAll($args, $pred, $this->maxSize);
        $this->suite->register($this);
    }

    function times(int $numTests) {
        $this->numTests = $numTests;
        return $this;
    }

    function maxSize(int $maxSize) {
        $this->maxSize = $maxSize;
        return $this;
    }

    function numTests() {
        return $this->numTests;
    }

    function name() {
        return $this->name;
    }

    function property(): Property {
        return $this->property;
    }
}
