<?php

namespace QCheck;

/**
 * A lazy monadic multi-way tree, used for shrinking.
 * This implementation is based on clojure.test.check.rose-tree.
 *
 * @package QCheck
 */
class RoseTree
{
    private $root;
    private $children;

    public function __construct($root, $children)
    {
        $this->root = $root;
        $this->children = $children;
    }

    public static function pure($val)
    {
        return new self($val, []);
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function fmap(callable $f)
    {
        return new self(
            call_user_func($f, $this->root),
            FP::rgen(FP::map(FP::method('fmap', $f), $this->children))
        );
    }

    public function join()
    {
        $innerRoot = $this->root->getRoot();
        $innerChildren = $this->root->getChildren();
        $newChildren = FP::rgen(FP::concat(
            FP::map(FP::method('join'), $this->children),
            $innerChildren
        ));
        return new self($innerRoot, $newChildren);
    }

    public function bind(callable $f)
    {
        return $this->fmap($f)->join();
    }

    public function filter(callable $pred)
    {
        return new self(
            $this->root,
            FP::rgen(FP::map(
                function (self $child) use ($pred) {
                    return $child->filter($pred);
                },
                FP::filter(
                    function (self $child) use ($pred) {
                        return call_user_func($pred, $child->getRoot());
                    },
                    $this->children
                )
            ))
        );
    }

    private static function permutations(array $roses)
    {
        foreach ($roses as $index => $rose) {
            foreach ($rose->children as $child) {
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
            FP::rgen(FP::map(
                FP::partial(
                    [__CLASS__,
                    'zip'],
                    $f
                ),
                FP::rgen(self::permutations($roses))
            ))
        );
    }

    private static function remove(array $roses)
    {
        return FP::concat(
            FP::map(function ($index) use ($roses) {
                return FP::excludeNth($index, $roses);
            }, array_keys($roses)),
            FP::rgen(self::permutations($roses))
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
                FP::rgen(FP::map(
                    FP::partial(
                        [__CLASS__,
                        'shrink'],
                        $f
                    ),
                    self::remove($roses)
                ))
            );
        }
    }
}
