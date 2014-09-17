<?php

namespace QCheck;

/**
 * This implementation is ripped straight from java.util.Random
 * Requires gmp extension and 64 bit integer precision. The constructor will throw if
 * PHP_INT_SIZE is less than 8.
 */
class Random {
    const ADDEND = 0xB;
    const MULTIPLIER = 0x5DEECE66D;
    private $seed;
    static function i32($long) {
        return $long << 32 >> 32;
    }
    static function rshiftu($a, $b) {
        if($b == 0) return $a;
        return ($a >> $b) & ~(1 << 63 >> ($b - 1));
    }
    static function mask($val) {
        return $val & ((1 << 48) - 1);
    }
    function __construct($seed = null) {
        if (PHP_INT_SIZE < 8) {
            throw new \RuntimeException('64 bit integer precision required');
        }
        $this->setSeed($seed !== null ?: intval(1000 * microtime(true)));
    }
    function setSeed($seed) {
        $this->seed = self::mask($seed ^ self::MULTIPLIER);
    }
    protected function next($bits) {
        $temp = gmp_intval(gmp_mul($this->seed, self::MULTIPLIER));
        $this->seed = self::mask($temp + self::ADDEND);
        return self::i32(self::rshiftu($this->seed, (48 - $bits)));
    }
    function nextDouble() {
        return (($this->next(26) << 27) + $this->next(27))
            / (double)(1 << 53);
    }
    function nextInt() {
        return $this->next(32);
    }
}
