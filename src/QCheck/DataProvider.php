<?php

namespace QCheck;

/**
 * This class serves as a helper to create data providers
 * for PHPUnit based testing.
 *
 * @package QCheck
 */
class DataProvider {
    /**
     * Transform an array of generators to an array of data that
     * can be used for PHPUnit test methods.
     *
     * @param Generator[] $generators
     * @param int $n number of data sets to generate.
     * @return array
     */
    public function provider(array $generators, $n = 100) {
        $tuples = call_user_func_array('QCheck\Generator::tuples', $generators);
        return $tuples->takeSamples($n);
    }
}