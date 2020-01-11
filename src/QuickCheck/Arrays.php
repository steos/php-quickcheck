<?php

namespace QuickCheck;

class Arrays
{
    /**
     * Return a copy of the given array where the value
     * at index $k has been changed to $val.
     *
     * @param array $arr
     * @param $k
     * @param $val
     * @return array
     */
    public static function assoc(array $arr, $k, $val)
    {
        // we're relying on arr being passed as value
        $arr[$k] = $val;
        return $arr;
    }

    /**
     * Return a copy of the array with the Nth element
     * removed.
     *
     * @param int $n
     * @param array $xs
     * @return array
     */
    public static function excludeNth($n, array $xs)
    {
        // we're relying on xs being passed as value
        array_splice($xs, $n, 1);
        return $xs;
    }

    /**
     * Return a copy of the given array with the given value
     * added at the end.
     *
     * @param array $xs
     * @param $x
     * @return array
     */
    public static function append(array $xs, $x)
    {
        $xs[] = $x;
        return $xs;
    }
}