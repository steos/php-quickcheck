<?php

namespace QCheck;

use QCheck\FP;
use QCheck\RoseTree;
use QCheck\Random;

class Generator {
    private $gen;

    private function __construct(callable $gen) {
        $this->gen = $gen;
    }

    function call($rng, $size) {
        return call_user_func($this->gen, $rng, $size);
    }

    static private function pureGen($value) {
        return new self(function($rng, $size) use ($value) {
            return $value;
        });
    }

    private function fmapGen(callable $k) {
        return new self(function($rng, $size) use ($k) {
            return call_user_func($k, call_user_func($this->gen, $rng, $size));
        });
    }

    private function bindGen(callable $k) {
        return new self(function($rng, $size) use ($k) {
            $f = call_user_func($k, $this->call($rng, $size));
            return $f->call($rng, $size);
        });
    }

    private static function sequence($ms) {
        return FP::reduce(
            function(Generator $acc, Generator $elem) {
                return $acc->bindGen(function($xs) use ($elem) {
                    return $elem->bindGen(function($y) use ($xs) {
                        return self::pureGen(FP::push($xs, $y));
                    });
                });
            },
            $ms,
            self::pureGen([])
        );
    }

    function fmap(callable $f) {
        return $this->fmapGen(function(RoseTree $rose) use ($f) {
            return $rose->fmap($f);
        });
    }

    function bind(callable $k) {
        return $this->bindGen(
            function(RoseTree $rose) use ($k) {
                $gen = new self(function($rng, $size) use ($rose, $k) {
                    return $rose->fmap($k)
                        ->fmap(FP::method('call', $rng, $size));
                });
                return $gen->fmapGen(FP::method('join'));
            }
        );
    }

    static function unit($value) {
        return self::pureGen(RoseTree::pure($value));
    }

    // helpers
    // --------------------------------------------------

    static function sizes($maxSize) {
        return FP::cycle(function() use ($maxSize) {
            return FP::range(0, $maxSize);
        });
    }

    function samples($maxSize = 100) {
        $rng = new Random();
        return FP::map(
            function($size) use ($rng) {
                return $this->call($rng, $size)->getRoot();
            },
            self::sizes($maxSize));
    }

    function takeSamples($num = 10) {
        return FP::realize(FP::take($num, $this->samples()));
    }


    // internal helpers
    // --------------------------------------------------

    static function halfs($n) {
        while (0 != $n) {
            yield $n;
            $n = intval($n/2);
        }
    }

    static function shrinkInt($i) {
        return FP::map(function($val) use ($i) {
            return $i - $val;
        }, self::halfs($i));
    }

    static function intRoseTree($val) {
        $me = [__CLASS__, 'intRoseTree'];
        return new RoseTree($val, FP::map($me, self::shrinkInt($val)));
    }

    static function sized(callable $sizedGen) {
        return new self(function($rng, $size) use ($sizedGen) {
            $gen = call_user_func($sizedGen, $size);
            return $gen->call($rng, $size);
        });
    }

    static function randRange(Random $rng, $lower, $upper) {
        if ($lower > $upper) throw new \InvalidArgumentException();
        $factor = $rng->nextDouble();
        return (int)floor($lower + ($factor * (1.0 + $upper) - $factor * $lower));
    }

    // combinators and helpers
    // --------------------------------------------------

    static function choose($lower, $upper) {
        return new self(function(Random $rng, $size) use ($lower, $upper) {
            $val = self::randRange($rng, $lower, $upper);
            $tree = self::intRoseTree($val);
            return $tree->filter(function($x) use ($lower, $upper) {
                return $x >= $lower && $x <= $upper;
            });
        });
    }

    static function tuples() {
        $seq = self::sequence(func_get_args());
        return $seq->bindGen(function($roses) {
            return self::pureGen(RoseTree::zip(FP::args(), $roses));
        });
    }

    static function ints() {
        return self::sized(function($size) {
            return self::choose(-$size, $size);
        });
    }

    static function lists(self $gen) {
        $sized = self::sized(function($s) {
            return self::choose(0, $s);
        });
        return $sized->bindGen(function($numRose) use ($gen) {
            $seq = self::sequence(FP::repeat($numRose->getRoot(), $gen));
            return $seq->bindGen(function($roses) {
                return self::pureGen(RoseTree::shrink(FP::args(), $roses));
            });
        });
    }

    static function chars() {
        return self::choose(0, 255)->fmap('chr');
    }

    static function asciiChars() {
        return self::choose(32, 126)->fmap('chr');
    }

    static function asciiStrings() {
        return self::lists(self::asciiChars())
            ->fmap(function($xs) {
                return implode('', $xs);
            });
    }

    static function maps(self $keygen, self $valgen) {
        return self::lists(self::tuples($keygen, $valgen))
            ->fmap(function($tuples) {
                $map = [];
                foreach ($tuples as $tuple) {
                    list($key, $val) = $tuple;
                    $map[$key] = $val;
                }
                return $map;
            });
    }

    static function oneOf() {
        $generators = func_get_args();
        $num = count($generators);
        if ($num < 2) {
            throw new \InvalidArgumentException();
        }
        return self::choose(0, $num - 1)
            ->bind(function($index) use ($generators) {
                return $generators[$index];
            });
    }

    static function forAll(array $args, callable $f) {
        $tuples = call_user_func_array([__CLASS__, 'tuples'], $args);
        return $tuples->fmap(function($args) use ($f) {
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
