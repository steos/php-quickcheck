<?php

namespace QuickCheck;

/**
 * A lazy monadic multi-way tree, used for shrinking.
 * This implementation is based on clojure.test.check.rose-tree.
 *
 * @package QuickCheck
 */
class ShrinkTree
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
            fn() => Lazy::map(fn(ShrinkTree $x) => $x->fmap($f), $this->getChildren()),
        );
    }

    public function join()
    {
        $innerRoot = $this->root->getRoot();
        return new self($innerRoot, function() {
            return Lazy::concat(
                Lazy::map(fn(self $x) => $x->join(), $this->getChildren()),
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
            fn() => Lazy::map(fn(self $child) => $child->filter($pred),
                Lazy::filter(
                    fn(self $child) => call_user_func($pred, $child->getRoot()),
                    $this->getChildren()
                )
            )
        );
    }

    private static function permutations(array $roses)
    {
        foreach ($roses as $index => $rose) {
            foreach ($rose->getChildren() as $child) {
                yield Arrays::assoc($roses, $index, $child);
            }
        }
    }

    public static function zip(callable $f, $roses)
    {
        return new self(
            call_user_func_array($f, Lazy::realize(Lazy::map(fn(self $node) => $node->getRoot(), $roses))),
            fn() => Lazy::map(fn($xs) => self::zip($f, $xs), self::permutations($roses))
        );
    }

    private static function remove(array $roses)
    {
        return Lazy::concat(
            Lazy::map(fn ($index) => Arrays::excludeNth($index, $roses), array_keys($roses)),
            self::permutations($roses)
        );
    }

    public static function shrink(callable $f, $roses)
    {
        $roses = Lazy::realize($roses);
        if (empty($roses)) {
            return self::pure(call_user_func($f));
        } else {
            return new self(
                call_user_func_array($f, Lazy::realize(Lazy::map(fn(self $node) => $node->getRoot(), $roses))),
                fn() => Lazy::map(fn($x) => self::shrink($f, $x), self::remove($roses)),
            );
        }
    }
}
