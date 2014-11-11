<?php

namespace QCheck;

/**
 * Helper for a functional programming style.
 *
 * @package QCheck
 */
class FP {
    /**
     * Return a callable with all the given arguments
     * already bound to the function (first parameter).
     *
     * @return callable
     */
    static function partial() {
        $args = func_get_args();
        $f = array_shift($args);
        return function() use ($f, $args) {
            return call_user_func_array($f, array_merge($args, func_get_args()));
        };
    }

    /**
     * Reduce any iterable collection using the callback. In case
     * of an array, array_reduce is used.
     *
     * @param callable $f
     * @param $xs
     * @param null $initial
     * @return mixed|null
     * @throws \InvalidArgumentException
     */
    static function reduce(callable $f, $xs, $initial = null) {
        if (is_array($xs)) {
            $initial = $initial !== null ? $initial : array_shift($xs);
            return array_reduce($xs, $f, $initial);
        }
        if ($xs instanceof \Iterator) {
            $xs->rewind();
            if (!$xs->valid()) {
                return $initial;
            }
            $acc = $initial;
            if ($acc === null) {
                $acc = $xs->current();
                $xs->next();
            }
            for (; $xs->valid(); $xs->next()) {
                $acc = call_user_func($f, $acc, $xs->current());
            }
            return $acc;
        }
        throw new \InvalidArgumentException();
    }

    /**
     * Compose the two functions
     *
     * @param callable $f
     * @param callable $g
     * @return callable
     */
    static function comp(callable $f, callable $g) {
        return function() use ($f, $g) {
            return call_user_func($f, call_user_func_array($g, func_get_args()));
        };
    }

    /**
     * Map a function to an iterable collection in a lazy way
     * using generators
     *
     * @param callable $f
     * @param \Iterator $coll
     * @return \Generator
     */
    static function map(callable $f, $coll) {
        foreach ($coll as $x) {
            yield call_user_func($f, $x);
        }
    }

    /**
     * Filter the given iterable collection using the callback in a
     * lazy way.
     *
     * @param callable $f
     * @param $coll
     * @return \Generator
     */
    static function filter(callable $f, $coll) {
        foreach ($coll as $x) {
            if (call_user_func($f, $x)) {
                yield $x;
            }
        }
    }

    /**
     * Transform the given iterable collection to a "real" array.
     *
     * @param array|\Iterator $it
     * @return array
     */
    static function realize($it) {
        if ($it instanceof \Iterator) {
            return iterator_to_array($it);
        }
        return $it;
    }

    /**
     * Iterate infinitely on the result of the given callable.
     *
     * @param callable $f
     * @return \Generator
     */
    static function cycle(callable $f) {
        while (true) {
            foreach ($f() as $x) {
                yield $x;
            }
        }
    }

    /**
     * Produce a range starting at $min and finishing with $max.
     * If $max is negative, the range will be infinite.
     *
     * @param int $min
     * @param int $max
     * @return \Generator
     */
    static function range($min = 0, $max = -1) {
        for ($i = $min; $max < 0 || $i < $max; ++$i) {
            yield $i;
        }
    }

    /**
     * Return at most $n elements from the given iterable collection in
     * a lazy fashion.
     *
     * @param int $n
     * @param \Iterator $it
     * @return \Generator
     */
    static function take($n, \Iterator $it) {
        for ($i = 0, $it->rewind(); $i < $n && $it->valid(); ++$i, $it->next()) {
            yield $it->current();
        }
    }

    /**
     * Lazily iterate over all iterable collection given as parameters
     * in order.
     *
     * @return \Generator
     */
    static function concat() {
        foreach (func_get_args() as $xs) {
            foreach ($xs as $x) {
                yield $x;
            }
        }
    }

    /**
     * repeat the value n times in a lazy fashion.
     *
     * @param int $n
     * @param mixed $val
     * @return \Generator
     */
    static function repeat($n, $val) {
        for ($i = 0; $i < $n; ++$i) {
            yield $val;
        }
    }

    /**
     * Return an Iterator allowing a rewind on the
     * given generator.
     *
     * @param \Generator $xs
     * @return RewindableIterator
     */
    static function rgen(\Generator $xs) {
        return new RewindableIterator($xs);
    }

    /**
     * Return a copy of the given array where the value
     * at index $k has been changed to $val.
     *
     * @param array $arr
     * @param $k
     * @param $val
     * @return array
     */
    static function assoc(array $arr, $k, $val) {
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
    static function excludeNth($n, array $xs) {
        // we're relying on xs being passed as value
        array_splice($xs, $n, 1);
        return $xs;
    }

    /**
     * Return a function that returns the given property
     * on any given object.
     *
     * @param string $name
     * @return callable
     */
    static function property($name) {
        return function($obj) use ($name) {
            return $obj->$name;
        };
    }

    /**
     * Return a function that calls the given method (first parameter)
     * on any given object with the given arguments (remaining parameters).
     *
     * @return callable
     * @throws \InvalidArgumentException
     */
    static function method() {
        $partialArgs = func_get_args();
        if (count($partialArgs) < 1) {
            throw new \InvalidArgumentException();
        }
        $name = array_shift($partialArgs);
        return function() use ($name, $partialArgs) {
            $args = func_get_args();
            if (count($args) < 1) {
                throw new \InvalidArgumentException();
            }
            $obj = array_shift($args);
            return call_user_func_array([&$obj, $name], array_merge($partialArgs, $args));
        };
    }

    /**
     * Return a copy of the given array with the given value
     * added at the end.
     *
     * @param array $xs
     * @param $x
     * @return array
     */
    static function push(array $xs, $x) {
        $xs[] = $x;
        return $xs;
    }

    /**
     * Returns a function that simply return that arguments
     * it receives.
     *
     * @return callable
     */
    static function args() {
        return function() {
            return func_get_args();
        };
    }

    /**
     * Create a string with the given char array.
     *
     * @param array $chars
     * @return string
     */
    static function str(array $chars) {
        return implode('', $chars);
    }

    /**
     * Return an iterator for the given collection if
     * possible.
     *
     * @param $xs
     * @return \ArrayIterator
     * @throws \InvalidArgumentException
     */
    static function iterator($xs) {
        if (is_array($xs)) {
            return new \ArrayIterator($xs);
        }
        if (!$xs instanceof \Iterator) {
            throw new \InvalidArgumentException();
        }
        return $xs;
    }

    /**
     * Return every Nth elements in the given collection in a lazy
     * fashion.
     *
     * @param int $n
     * @param $coll
     * @return \Generator
     */
    static function takeNth($n, $coll) {
        $coll = self::iterator($coll);
        for ($coll->rewind(); $coll->valid(); $coll->next()) {
            yield $coll->current();
            for ($i = 0; $i < $n - 1 && $coll->valid(); ++$i) {
                $coll->next();
            }
        }
    }

    /**
     * Return the given collection partitioned in n sized segments
     * in a lazy fashion.
     *
     * @param int $n
     * @param $coll
     * @return \Generator
     */
    static function partition($n, $coll) {
        $coll = self::iterator($coll);
        for ($coll->rewind(); $coll->valid();) {
            $partition = [];
            for ($i = 0; $i < $n && $coll->valid(); ++$i, $coll->next()) {
                $partition[] = $coll->current();
            }
            yield $partition;
        }
    }
}
