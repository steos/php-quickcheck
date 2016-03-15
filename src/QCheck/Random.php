<?php

namespace QCheck;

/**
 * This implementation is ripped straight from java.util.Random
 * Requires gmp extension and 64 bit integer precision. The constructor will throw if
 * PHP_INT_SIZE is less than 8.
 */
class Random
{
    const ADDEND = 0xB;
    const MULTIPLIER = 0x5DEECE66D;
    private $seed;
    public static function i32($long)
    {
        return $long << 32 >> 32;
    }
    public static function rshiftu($a, $b)
    {
        if ($b == 0) {
            return $a;
        }

        return ($a >> $b) & ~(1 << 63 >> ($b - 1));
    }
    public static function mask($val)
    {
        return $val & ((1 << 48) - 1);
    }
    public function __construct($seed = null)
    {
        if (PHP_INT_SIZE < 8) {
            throw new \RuntimeException('64 bit integer precision required');
        }
        $this->setSeed($seed !== null ? $seed : intval(1000 * microtime(true)));
    }
    public function setSeed($seed)
    {
        $this->seed = self::mask($seed ^ self::MULTIPLIER);
    }
    protected function next($bits)
    {
        $temp = function_exists('gmp_mul') ?
            gmp_intval(gmp_mul($this->seed, self::MULTIPLIER)) :
            self::int_mul($this->seed, self::MULTIPLIER);
        $this->seed = self::mask($temp + self::ADDEND);

        return self::i32(self::rshiftu($this->seed, (48 - $bits)));
    }
    public function nextDouble()
    {
        return (($this->next(26) << 27) + $this->next(27))
            / (double) (1 << 53);
    }
    public function nextInt()
    {
        return $this->next(32);
    }

    // does Java/C/Go-like overflow
    // http://stackoverflow.com/questions/4456442/multiplication-of-two-integers-using-bitwise-operators
    public static function int_mul($a, $b)
    {
        $result = 0;
        while ($b != 0) {
            // Iterate the loop till b==0
        if ($b & 01) {                // is $b odd?
          $result = self::int_add($result, $a); // int_add result
        }
            $a <<= 1;               // Left shifting the value contained in 'a' by 1
                                    // multiplies a by 2 for each loop
        $b >>= 1;                   // Right shifting the value contained in 'b' by 1.
        }

        return $result;
    }

    // addition with only shifts and ands
    public static function int_add($x, $y)
    {
        if ($y == 0) {
            return $x;
        } else {
            return self::int_add($x ^ $y, ($x & $y) << 1);
        }
    }
}
