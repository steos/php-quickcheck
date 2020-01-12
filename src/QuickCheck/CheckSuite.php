<?php

namespace QuickCheck;

class CheckSuite
{
    private $checks = [];
    private $file;

    function __construct(string $file) {
        $this->file = $file;
    }

    function register(Check $check) {
        $this->checks[] = $check;
    }

    function empty() {
        return empty($this->checks);
    }

    /**
     * @return Check[]
     */
    function checks() {
        return $this->checks;
    }

    function file() {
        return $this->file;
    }
}
