<?php

namespace QuickCheck;

/**
 * A monadic generator that produces lazy shrink trees.
 * Based on clojure.test.check.generators.
 *
 * @package QuickCheck
 */
class Generator
{
    private $gen;

    private function __construct(callable $gen)
    {
        $this->gen = $gen;
    }

    /**
     * invokes the generator with the given RNG and size
     *
     * @param Random $rng
     * @param int $size
     * @return ShrinkTree
     */
    public function call(Random $rng, $size)
    {
        return call_user_func($this->gen, $rng, $size);
    }

    public static function pureGen($value)
    {
        return new self(function ($rng, $size) use ($value) {
            return $value;
        });
    }

    public function fmapGen(callable $k)
    {
        return new self(function ($rng, $size) use ($k) {
            return call_user_func($k, call_user_func($this->gen, $rng, $size));
        });
    }

    public function bindGen(callable $k)
    {
        return new self(function ($rng, $size) use ($k) {
            $f = call_user_func($k, $this->call($rng, $size));
            return $f->call($rng, $size);
        });
    }

    /**
     * turns a list of generators into a generator of a list
     *
     * @param Generator[]|\Iterator $ms
     * @return Generator
     */
    private static function sequence($ms)
    {
        return Lazy::reduce(
            function (Generator $acc, Generator $elem) {
                return $acc->bindGen(function ($xs) use ($elem) {
                    return $elem->bindGen(function ($y) use ($xs) {
                        return self::pureGen(Arrays::push($xs, $y));
                    });
                });
            },
            $ms,
            self::pureGen([])
        );
    }

    /**
     * maps function f over the values produced by this generator
     *
     * @param callable $f
     * @return Generator
     */
    public function fmap(callable $f)
    {
        return $this->fmapGen(function (ShrinkTree $rose) use ($f) {
            return $rose->fmap($f);
        });
    }

    /**
     * creates a new generator that passes the result of this generator
     * to the callable $k which should return a new generator.
     *
     * @param callable $k
     * @return Generator
     */
    public function bind(callable $k)
    {
        return $this->bindGen(
            function (ShrinkTree $rose) use ($k) {
                $gen = new self(function ($rng, $size) use ($rose, $k) {
                    return $rose->fmap($k)
                        ->fmap(Functions::method('call', $rng, $size));
                });
                return $gen->fmapGen(Functions::method('join'));
            }
        );
    }

    /**
     * creates a generator that always returns $value and never shrinks
     *
     * @param mixed $value
     * @return Generator
     */
    public static function unit($value)
    {
        return self::pureGen(ShrinkTree::pure($value));
    }

    // helpers
    // --------------------------------------------------

    public static function sizes($maxSize)
    {
        return Lazy::cycle(function () use ($maxSize) {
            return Lazy::range(0, $maxSize);
        });
    }

    /**
     * returns an infinite sequence of random samples from this generator bounded by $maxSize
     *
     * @param int $maxSize
     * @return \Generator
     */
    public function samples($maxSize = 100)
    {
        $rng = new Random();
        return Lazy::map(
            function ($size) use ($rng) {
                return $this->call($rng, $size)->getRoot();
            },
            self::sizes($maxSize)
        );
    }

    /**
     * returns an array of $num random samples from this generator
     *
     * @param int $num
     * @return array
     */
    public function takeSamples($num = 10)
    {
        return Lazy::realize(Lazy::take($num, $this->samples()));
    }


    // internal helpers
    // --------------------------------------------------

    private static function halfs($n)
    {
        while (0 != $n) {
            yield $n;
            $n = intval($n/2);
        }
    }

    private static function shrinkInt($i)
    {
        return Lazy::map(function ($val) use ($i) {
            return $i - $val;
        }, self::halfs($i));
    }

    public static function intRoseTree($val)
    {
        $me = [__CLASS__, 'intRoseTree'];
        return new ShrinkTree($val, function() use ($me, $val) {
            return Lazy::map($me, self::shrinkInt($val));
        });
    }

    private static function sized(callable $sizedGen)
    {
        return new self(function ($rng, $size) use ($sizedGen) {
            $gen = call_user_func($sizedGen, $size);
            return $gen->call($rng, $size);
        });
    }

    private static function randRange(Random $rng, $lower, $upper)
    {
        if ($lower > $upper) {
            throw new \InvalidArgumentException();
        }
        $factor = $rng->nextDouble();
        return (int)floor($lower + ($factor * (1.0 + $upper) - $factor * $lower));
    }

    private static function getArgs(array $args)
    {
        if (count($args) == 1 && is_array($args[0])) {
            return $args[0];
        }
        return $args;
    }

    // combinators and helpers
    // --------------------------------------------------

    /**
     * creates a generator that returns numbers in the range $lower to $upper, inclusive
     *
     * @param int $lower
     * @param int $upper
     * @return Generator
     */
    public static function choose($lower, $upper)
    {
        return new self(function (Random $rng, $size) use ($lower, $upper) {
            $val = self::randRange($rng, $lower, $upper);
            $tree = self::intRoseTree($val);
            return $tree->filter(function ($x) use ($lower, $upper) {
                return $x >= $lower && $x <= $upper;
            });
        });
    }

    /**
     * creates a new generator based on this generator with size always bound to $n
     *
     * @param int $n
     * @return Generator
     */
    public function resize($n)
    {
        return new self(function ($rng, $size) use ($n) {
            return $this->call($rng, $n);
        });
    }

    /**
     * creates a new generator that returns an array whose elements are chosen
     * from the list of given generators. Individual elements shrink according to
     * their generator but the array will never shrink in count.
     *
     * Accepts either a variadic number of args or a single array of generators.
     *
     * Example:
     * Gen::tuples(Gen::booleans(), Gen::ints())
     * Gen::tuples([Gen::booleans(), Gen::ints()])
     *
     * @return Generator
     */
    public static function tuples()
    {
        $seq = self::sequence(self::getArgs(func_get_args()));
        return $seq->bindGen(function ($roses) {
            return self::pureGen(ShrinkTree::zip(Functions::args(), $roses));
        });
    }

    /**
     * creates a generator that returns a positive or negative integer bounded
     * by the generators size parameter.
     *
     * @return Generator
     */
    public static function ints()
    {
        return self::sized(function ($size) {
            return self::choose(-$size, $size);
        });
    }

    /**
     * creates a generator that produces arrays whose elements
     * are chosen from $gen.
     *
     * @param Generator $gen
     * @return Generator
     */
    public static function arraysOf(self $gen)
    {
        $sized = self::sized(function ($s) {
            return self::choose(0, $s);
        });
        return $sized->bindGen(function (ShrinkTree $numRose) use ($gen) {
            $seq = self::sequence(Lazy::repeat($numRose->getRoot(), $gen));
            return $seq->bindGen(function ($roses) {
                return self::pureGen(ShrinkTree::shrink(Functions::args(), $roses));
            });
        });
    }

    /**
     * creates a generator that produces arrays whose elements
     * are chosen from this generator.
     *
     * @return Generator
     */
    public function intoArrays()
    {
        return self::arraysOf($this);
    }

    /**
     * creates a generator that produces characters from 0-255
     *
     * @return Generator
     */
    public static function chars()
    {
        return self::choose(0, 255)->fmap('chr');
    }

    /**
     * creates a generator that produces only ASCII characters
     *
     * @return Generator
     */
    public static function asciiChars()
    {
        return self::choose(32, 126)->fmap('chr');
    }

    /**
     * creates a generator that produces alphanumeric characters
     *
     * @return Generator
     */
    public static function alphaNumChars()
    {
        return self::oneOf(
            self::choose(48, 57),
            self::choose(65, 90),
            self::choose(97, 122)
        )->fmap('chr');
    }

    /**
     * creates a generator that produces only alphabetic characters
     *
     * @return Generator
     */
    public static function alphaChars()
    {
        return self::oneOf(
            self::choose(65, 90),
            self::choose(97, 122)
        )->fmap('chr');
    }

    public function dontShrink() {
        return $this->bindGen(function(ShrinkTree $x) {
            return self::unit($x->getRoot());
        });
    }

    public function toStrings() {
        return $this->fmap(function($x) {
            return is_array($x) ? implode('', $x) : (string)$x;
        });
    }

    /**
     * creates a generator that produces strings; may contain unprintable chars
     *
     * @return Generator
     */
    public static function strings()
    {
        return self::chars()->intoArrays()->toStrings();
    }

    /**
     * creates a generator that produces ASCII strings
     *
     * @return Generator
     */
    public static function asciiStrings()
    {
        return self::asciiChars()->intoArrays()->toStrings();
    }

    /**
     * creates a generator that produces alphanumeric strings
     *
     * @return Generator
     */
    public static function alphaNumStrings()
    {
        return self::alphaNumChars()->intoArrays()->toStrings();
    }

    /**
     * creates a generator that produces alphabetic strings
     *
     * @return Generator
     */
    public static function alphaStrings()
    {
        return self::alphaChars()->intoArrays()->toStrings();
    }

    /**
     * creates a generator that produces arrays with keys chosen from $keygen
     * and values chosen from $valgen.
     *
     * @param Generator $keygen
     * @param Generator $valgen
     *
     * @return Generator
     */
    public static function maps(self $keygen, self $valgen)
    {
        return self::tuples($keygen, $valgen)
            ->intoArrays()
            ->fmap(function ($tuples) {
                $map = [];
                foreach ($tuples as $tuple) {
                    list($key, $val) = $tuple;
                    $map[$key] = $val;
                }
                return $map;
            });
    }

    /**
     * creates a generator that produces arrays with keys chosen from
     * this generator and values chosen from $valgen.
     *
     * @param Generator $valgen
     * @return Generator
     */
    public function mapsTo(self $valgen)
    {
        return self::maps($this, $valgen);
    }

    /**
     * creates a generator that produces arrays with keys chosen from
     * $keygen and values chosen from this generator.
     *
     * @param Generator $keygen
     * @return Generator
     */
    public function mapsFrom(self $keygen)
    {
        return self::maps($keygen, $this);
    }

    /**
     * creates a generator that produces arrays with the same keys as in $map
     * where each corresponding value is chosen from a specified generator.
     *
     * Example:
     * Gen::mapsWith(
     *   'foo' => Gen::booleans(),
     *   'bar' => Gen::ints()
     * )
     *
     * @param array $map
     * @return Generator
     */
    public static function mapsWith(array $map)
    {
        return self::tuples($map)->fmap(function ($vals) use ($map) {
            return array_combine(array_keys($map), $vals);
        });
    }

    /**
     * creates a new generator that randomly chooses a value from the list
     * of provided generators. Shrinks toward earlier generators as well as shrinking
     * the generated value itself.
     *
     * Accepts either a variadic number of args or a single array of generators.
     *
     * Example:
     * Gen::oneOf(Gen::booleans(), Gen::ints())
     * Gen::oneOf([Gen::booleans(), Gen::ints()])
     *
     * @return Generator
     */
    public static function oneOf()
    {
        $generators = self::getArgs(func_get_args());
        $num = count($generators);
        if ($num < 2) {
            throw new \InvalidArgumentException();
        }
        return self::choose(0, $num - 1)
            ->bind(function ($index) use ($generators) {
                return $generators[$index];
            });
    }

    /**
     * creates a generator that randomly chooses from the specified values
     *
     * Accepts either a variadic number of args or a single array of values.
     *
     * Example:
     * Gen::elements('foo', 'bar', 'baz')
     * Gen::elements(['foo', 'bar', 'baz'])
     *
     * @return Generator
     */
    public static function elements()
    {
        $coll = self::getArgs(func_get_args());
        if (empty($coll)) {
            throw new \InvalidArgumentException();
        }
        return self::choose(0, count($coll) - 1)
            ->bindGen(function (ShrinkTree $rose) use ($coll) {
                return self::pureGen($rose->fmap(
                    function ($index) use ($coll) {
                        return $coll[$index];
                    }
                ));
            });
    }

    /**
     * creates a new generator that generates values from this generator such that they
     * satisfy callable $pred.
     * At most $maxTries attempts will be made to generate a value that satisfies the
     * predicate. At every retry the size parameter will be increased. In case of failure
     * an exception will be thrown.
     *
     * @param callable $pred
     * @param int $maxTries
     * @return Generator
     * @throws \RuntimeException
     */
    public function suchThat(callable $pred, $maxTries = 10)
    {
        return new self(function ($rng, $size) use ($pred, $maxTries) {
            for ($i = 0; $i < $maxTries; ++$i) {
                $value = $this->call($rng, $size);
                if (call_user_func($pred, $value->getRoot())) {
                    return $value->filter($pred);
                }
                $size++;
            }
            throw new \RuntimeException(
                "couldn't satisfy such-that predicate after $maxTries tries."
            );
        });
    }

    public function notEmpty($maxTries = 10)
    {
        return $this->suchThat(function ($x) {
            return !empty($x);
        }, $maxTries);
    }

    /**
     * creates a generator that produces true or false. Shrinks to false.
     *
     * @return Generator
     */
    public static function booleans()
    {
        return self::elements(false, true);
    }

    /**
     * creates a generator that produces positive integers bounded by
     * the generators size parameter.
     *
     * @return Generator
     */
    public static function posInts()
    {
        return self::ints()->fmap(function ($x) {
            return abs($x);
        });
    }

    /**
     * creates a generator that produces negative integers bounded by
     * the generators size parameter.
     *
     * @return Generator
     */
    public static function negInts()
    {
        return self::ints()->fmap(function ($x) {
            return -abs($x);
        });
    }

    /**
     * creates a generator that produces strictly positive integers bounded by
     * the generators size parameter.
     *
     * @return Generator
     */
    public static function strictlyPosInts()
    {
        return self::ints()->fmap(function ($x) {
            return abs($x)+1;
        });
    }

    /**
     * creates a generator that produces strictly negative integers bounded by
     * the generators size parameter.
     *
     * @return Generator
     */
    public static function strictlyNegInts()
    {
        return self::ints()->fmap(function ($x) {
            return -abs($x)-1;
        });
    }

    /**
     * creates a generator that produces values from specified generators based on
     * likelihoods. The likelihood of a generator being chosen is its likelihood divided
     * by the sum of all likelihoods.
     *
     * Example:
     * Gen::frequency(
     *   5, Gen::ints(),
     *   3, Gen::booleans(),
     *   2, Gen::alphaStrings()
     * )
     *
     * @return Generator
     */
    public static function frequency()
    {
        $args = func_get_args();
        $argc = count($args);
        if ($argc < 2 || $argc % 2 != 0) {
            throw new \InvalidArgumentException();
        }
        $total = array_sum(Lazy::realize(Lazy::takeNth(2, $args)));
        $pairs = Lazy::realize(Lazy::partition(2, $args));
        return self::choose(1, $total)->bindGen(
            function (ShrinkTree $rose) use ($pairs) {
                $n = $rose->getRoot();
                foreach ($pairs as $pair) {
                    list($chance, $gen) = $pair;
                    if ($n <= $chance) {
                        return $gen;
                    }
                    $n = $n - $chance;
                }
            }
        );
    }

    public static function simpleTypes()
    {
        return self::oneOf(
            self::ints(),
            self::chars(),
            self::strings(),
            self::booleans()
        );
    }

    public static function simplePrintableTypes()
    {
        return self::oneOf(
            self::ints(),
            self::asciiChars(),
            self::asciiStrings(),
            self::booleans()
        );
    }

    public static function containerTypes(self $innerType)
    {
        return self::oneOf(
            $innerType->intoArrays(),
            $innerType->mapsFrom(self::oneOf(self::ints(), self::strings()))
        );
    }

    private static function recursiveHelper($container, $scalar, $scalarSize, $childrenSize, $height)
    {
        if ($height == 0) {
            return $scalar->resize($scalarSize);
        } else {
            return call_user_func(
                $container,
                self::recursiveHelper(
                    $container,
                    $scalar,
                    $scalarSize,
                    $childrenSize,
                    $height - 1
                )
            );
        }
    }

    public static function recursive(callable $container, self $scalar)
    {
        return self::sized(function ($size) use ($container, $scalar) {
            return self::choose(1, 5)->bind(
                function ($height) use ($container, $scalar, $size) {
                    $childrenSize = pow($size, 1/$height);
                    return self::recursiveHelper(
                        $container,
                        $scalar,
                        $size,
                        $childrenSize,
                        $height
                    );
                }
            );
        });
    }

    public static function any()
    {
        return self::recursive(
            [__CLASS__, 'containerTypes'],
            self::simpleTypes()
        );
    }

    public static function anyPrintable()
    {
        return self::recursive(
            [__CLASS__, 'containerTypes'],
            self::simplePrintableTypes()
        );
    }

    public static function forAll(array $args, callable $f)
    {
        $tuples = call_user_func_array([__CLASS__, 'tuples'], $args);
        return $tuples->fmap(function ($args) use ($f) {
            try {
                $result = call_user_func_array($f, $args);
            } catch (\Exception $e) {
                $result = $e;
            }
            return ['result' => $result,
                    'function' => $f,
                    'args' => $args];
        });
    }
}
