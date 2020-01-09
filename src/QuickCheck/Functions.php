<?php

namespace QuickCheck;

class Functions
{
    /**
     * Return a function that calls the given method (first parameter)
     * on any given object with the given arguments (remaining parameters).
     *
     * @return callable
     * @throws \InvalidArgumentException
     */
    public static function method()
    {
        $partialArgs = func_get_args();
        if (count($partialArgs) < 1) {
            throw new \InvalidArgumentException();
        }
        $name = array_shift($partialArgs);
        return function () use ($name, $partialArgs) {
            $args = func_get_args();
            if (count($args) < 1) {
                throw new \InvalidArgumentException();
            }
            $obj = array_shift($args);
            return call_user_func_array([&$obj, $name], array_merge($partialArgs, $args));
        };
    }

    /**
     * Returns a function that simply return that arguments
     * it receives.
     *
     * @return callable
     */
    public static function args()
    {
        return function () {
            return func_get_args();
        };
    }
}