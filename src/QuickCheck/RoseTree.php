<?php

namespace QuickCheck;

/**
 * A lazy monadic multi-way tree, used for shrinking.
 * This implementation is based on clojure.test.check.rose-tree.
 *
 * @package QuickCheck
 */
class RoseTree
{
    private $root;
    private $children;

    public function __construct($root, callable $children)
    {
        $this->root = $root;
        $this->children = $children;
    }

    public static function pure($val)
    {
        return new self($val, function() { return []; });
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function getChildren()
    {
        return call_user_func($this->children);
    }

    public function fmap(callable $f)
    {
        return new self(
            call_user_func($f, $this->root),
            function() use ($f) { return FP::map(FP::method('fmap', $f), $this->getChildren()); }
        );
    }

    public function join()
    {
        $innerRoot = $this->root->getRoot();
        return new self($innerRoot, function() {
            return FP::concat(
                FP::map(FP::method('join'), $this->getChildren()),
                $this->root->getChildren()
            );
        });
    }

    public function bind(callable $f)
    {
        return $this->fmap($f)->join();
    }

    public function filter(callable $pred)
    {
        return new self(
            $this->root,
            function() use ($pred) {
                return FP::map(
                    function (self $child) use ($pred) {
                        return $child->filter($pred);
                    },
                    FP::filter(
                        function (self $child) use ($pred) {
                            return call_user_func($pred, $child->getRoot());
                        },
                        $this->getChildren()
                    )
                );
            }
        );
    }

    private static function permutations(array $roses)
    {
        foreach ($roses as $index => $rose) {
            foreach ($rose->getChildren() as $child) {
                yield FP::assoc($roses, $index, $child);
            }
        }
    }

    public static function zip(callable $f, $roses)
    {
        return new self(
            call_user_func_array(
                $f,
                FP::realize(FP::map(FP::method('getRoot'), $roses))
            ),
            function() use ($f, $roses) {
                return FP::map(
                    FP::partial(
                        [__CLASS__,
                        'zip'],
                        $f
                    ),
                    self::permutations($roses)
                );
            }
        );
    }

    private static function remove(array $roses)
    {
        return FP::concat(
            FP::map(function ($index) use ($roses) {
                return FP::excludeNth($index, $roses);
            }, array_keys($roses)),
            self::permutations($roses)
        );
    }

    public static function shrink(callable $f, $roses)
    {
        $roses = FP::realize($roses);
        if (empty($roses)) {
            return self::pure(call_user_func($f));
        } else {
            return new self(
                call_user_func_array(
                    $f,
                    FP::realize(FP::map(FP::method('getRoot'), $roses))
                ),
                function() use ($f, $roses) {
                    return FP::map(
                        FP::partial(
                            [__CLASS__,
                            'shrink'],
                            $f
                        ),
                        self::remove($roses)
                    );
                }
            );
        }
    }
}
