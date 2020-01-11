<?php

namespace QuickCheck;

class PropertyTest
{
    private $result;
    private $predicate;
    private $arguments;

    function __construct($result, callable $predicate, array $arguments)
    {
        $this->result = $result;
        $this->predicate = $predicate;
        $this->arguments = $arguments;
    }

    static function isFailure(self $test)
    {
        return !$test->result || $test->result instanceof \Exception;
    }

    public function result()
    {
        return $this->result;
    }

    public function predicate(): callable
    {
        return $this->predicate;
    }

    public function arguments(): array
    {
        return $this->arguments;
    }
}
