<?php

namespace QuickCheck;

/**
 * Utilities for working with lazy sequences
 *
 * @package QuickCheck
 */
class Lazy
{
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
    public static function reduce(callable $f, $xs, $initial = null)
    {
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
     * Maps a function over an iterable collection in a lazy way
     * using generators
     *
     * @param callable $f
     * @param \Iterable $coll
     * @return \Generator
     */
    public static function map(callable $f, $coll)
    {
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
    public static function filter(callable $f, $coll)
    {
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
    public static function realize($it)
    {
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
    public static function cycle(callable $f)
    {
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
    public static function range($min = 0, $max = -1)
    {
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
    public static function take($n, \Iterator $it)
    {
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
    public static function concat()
    {
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
    public static function repeat($n, $val)
    {
        for ($i = 0; $i < $n; ++$i) {
            yield $val;
        }
    }

    /**
     * Return an iterator for the given collection if
     * possible.
     *
     * @param $xs
     * @return \Iterator
     * @throws \InvalidArgumentException
     */
    public static function iterator($xs)
    {
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
    public static function takeNth($n, $coll)
    {
        $coll = self::iterator($coll);
        for ($coll->rewind(); $coll->valid(); $coll->valid() && $coll->next()) {
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
    public static function partition($n, $coll)
    {
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
