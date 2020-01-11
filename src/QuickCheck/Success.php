<?php

namespace QuickCheck;

class Success implements CheckResult
{
    private $numTests;
    private $seed;

    function __construct(int $numTests, int $seed) {
        $this->numTests = $numTests;
        $this->seed = $seed;
    }

    function numTests(): int
    {
        return $this->numTests;
    }

    function seed(): int
    {
        return $this->seed;
    }

    function isSuccess(): bool
    {
        return true;
    }

    function isFailure(): bool
    {
        return false;
    }
}
