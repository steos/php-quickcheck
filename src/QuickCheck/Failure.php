<?php

namespace QuickCheck;

class Failure implements CheckResult
{
    private $numTests;
    private $seed;
    private $shrunk;
    private $failed;

    function __construct(int $numTests, int $seed, PropertyTest $failed, ShrinkResult $shrunk) {
        $this->numTests = $numTests;
        $this->seed = $seed;
        $this->failed = $failed;
        $this->shrunk = $shrunk;
    }

    public function numTests(): int
    {
        return $this->numTests;
    }

    public function seed(): int
    {
        return $this->seed;
    }

    public function shrunk(): ShrinkResult
    {
        return $this->shrunk;
    }

    function test(): PropertyTest {
        return $this->failed;
    }

    function isSuccess(): bool {
        return false;
    }

    function isFailure(): bool {
        return true;
    }

    function dump(callable $encode): void
    {
        echo 'Failed after ', $this->numTests(), ' tests', PHP_EOL;
        echo 'Seed: ', $this->seed(), PHP_EOL;
        echo 'Failing input:', PHP_EOL;
        echo call_user_func($encode, $this->test()->arguments()), PHP_EOL;
        echo 'Smallest failing input:', PHP_EOL;
        echo call_user_func($encode, $this->shrunk()->test()->arguments()), PHP_EOL;
    }
}
