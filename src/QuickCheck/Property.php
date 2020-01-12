<?php

namespace QuickCheck;

class Property
{
    private $generator;
    private $maxSize;

    private function __construct(Generator $generator, int $maxSize = 200)
    {
        $this->generator = $generator;
        $this->maxSize = $maxSize;
    }

    public static function forAll(array $args, callable $f, int $maxSize = 200): self
    {
        return new self(Generator::tuples($args)->map(function ($args) use ($f) {
            try {
                $result = call_user_func_array($f, $args);
            } catch (\Exception $e) {
                $result = $e;
            }
            return new PropertyTest($result, $f, $args);
        }), $maxSize);
    }

    function withMaxSize(int $maxSize) {
        return new Property($this->generator, $maxSize);
    }

    function randomTests(Random $rng) {
        return Lazy::map(
            function($size) use ($rng) {
                return $this->generator->call($rng, $size);
            },
            Generator::sizes($this->maxSize)
        );
    }

    static function check(self $prop, int $numTests, int $seed = null): CheckResult {
        $seed = $seed ?? intval(microtime(true) * 1000);
        $rng = new Random($seed);
        /** @var ShrinkTreeNode[] $tests */
        $tests = Lazy::take($numTests, $prop->randomTests($rng));
        $testCount = 0;
        foreach ($tests as $node) {
            $testCount++;
            /** @var PropertyTest $test */
            $test = $node->getValue();
            if (PropertyTest::isFailure($test)) {
                $failed = $test;
                $shrunk = new ShrinkResult(0, 0, $test);
                foreach (ShrinkResult::searchSmallest($node) as $result) {
                    $shrunk = $result;
                }
                return new Failure($testCount, $seed, $failed, $shrunk);
            }
        }
        return new Success($testCount, $seed);
    }

    function maxSize() {
        return $this->maxSize;
    }
}
