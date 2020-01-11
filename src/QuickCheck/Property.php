<?php

namespace QuickCheck;

class Property
{
    private $generator;

    private function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    public static function forAll(array $args, callable $f): self
    {
        return new self(Generator::tuples($args)->map(function ($args) use ($f) {
            try {
                $result = call_user_func_array($f, $args);
            } catch (\Exception $e) {
                $result = $e;
            }
            return new PropertyTest($result, $f, $args);
        }));
    }

    function randomTests(Random $rng, int $maxSize) {
        return Lazy::map(
            function($size) use ($rng) {
                return $this->generator->call($rng, $size);
            },
            Generator::sizes($maxSize)
        );
    }

    static function check(self $prop, int $numTests, $opts = []): CheckResult {
        $seed = @$opts['seed'] ?? intval(microtime(true) * 1000);
        $maxSize = @$opts['max_size'] ?? 200;
        $rng = new Random($seed);
        /** @var ShrinkTreeNode[] $tests */
        $tests = Lazy::take($numTests, $prop->randomTests($rng, $maxSize));
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
}
