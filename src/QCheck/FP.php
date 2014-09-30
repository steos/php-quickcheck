<?php

namespace QCheck;

class FP {

    static function partial() {
        $args = func_get_args();
        $f = array_shift($args);
        return function() use ($f, $args) {
            return call_user_func_array($f, array_merge($args, func_get_args()));
        };
    }

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

    static function comp(callable $f, callable $g) {
        return function() use ($f, $g) {
            return call_user_func($f, call_user_func_array($g, func_get_args()));
        };
    }

    static function map(callable $f, $coll) {
        foreach ($coll as $x) {
            yield call_user_func($f, $x);
        }
    }

    static function filter(callable $f, $coll) {
        foreach ($coll as $x) {
            if (call_user_func($f, $x)) {
                yield $x;
            }
        }
    }

    static function realize($it) {
        if ($it instanceof \Iterator) {
            return iterator_to_array($it);
        }
        return $it;
    }

    static function cycle(callable $f) {
        while (true) {
            foreach ($f() as $x) {
                yield $x;
            }
        }
    }

    static function range($min = 0, $max = -1) {
        for ($i=0;$max<0||$i<$max;++$i) {
            yield $i;
        }
    }

    static function take($n, \Iterator $it) {
        for ($i = 0, $it->rewind(); $i < $n && $it->valid(); ++$i, $it->next()) {
            yield $it->current();
        }
    }

    static function concat() {
        foreach (func_get_args() as $xs) {
            foreach ($xs as $x) {
                yield $x;
            }
        }
    }

    static function repeat($n, $val) {
        for ($i = 0; $i < $n; ++$i) {
            yield $val;
        }
    }

    static function rgen(\Generator $xs) {
        return new RewindableIterator($xs);
    }

    static function assoc(array $arr, $k, $val) {
        // we're relying on arr being passed as value
        $arr[$k] = $val;
        return $arr;
    }

    static function excludeNth($n, array $xs) {
        // we're relying on xs being passed as value
        array_splice($xs, $n, 1);
        return $xs;
    }

    static function property($name) {
        return function($obj) use ($name) {
            return $obj->$name;
        };
    }

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

    static function push(array $xs, $x) {
        $xs[] = $x;
        return $xs;
    }

    static function args() {
        return function() {
            return func_get_args();
        };
    }

    static function str(array $chars) {
        return implode('', $chars);
    }

    static function iterator($xs) {
        if (is_array($xs)) {
            return new \ArrayIterator($xs);
        }
        if (!$xs instanceof \Iterator) {
            throw new \InvalidArgumentException();
        }
        return $xs;
    }

    static function takeNth($n, $coll) {
        $coll = self::iterator($coll);
        for ($coll->rewind(); $coll->valid(); $coll->next()) {
            yield $coll->current();
            for ($i = 0; $i < $n - 1 && $coll->valid(); ++$i) {
                $coll->next();
            }
        }
    }

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
