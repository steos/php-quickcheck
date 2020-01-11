<?php

namespace QuickCheck;

/**
 * A lazy monadic multi-way tree, used for shrinking.
 * This implementation is based on clojure.test.check.rose-tree.
 *
 * @package QuickCheck
 */
class ShrinkTreeNode
{
    private $value;
    private $children;

    public function __construct($value, callable $children)
    {
        $this->value = $value;
        $this->children = $children;
    }

    public static function pure($val)
    {
        return new self($val, function () {
            return [];
        });
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getChildren()
    {
        return call_user_func($this->children);
    }

    public function map(callable $f)
    {
        return new self(
            call_user_func($f, $this->value),
            function () use ($f) {
                return Lazy::map(
                    function (ShrinkTreeNode $x) use ($f) {
                        return $x->map($f);
                    },
                    $this->getChildren()
                );
            }
        );
    }

    public function join()
    {
        $innerRoot = $this->value->getValue();
        return new self($innerRoot, function() {
            return Lazy::concat(
                Lazy::map(
                    function (self $x) {
                        return $x->join();
                    },
                    $this->getChildren()
                ),
                $this->value->getChildren()
            );
        });
    }

    public function chain(callable $f)
    {
        return $this->map($f)->join();
    }

    public function filter(callable $pred)
    {
        return new self(
            $this->value,
            function () use ($pred) {
                return Lazy::map(
                    function (self $child) use ($pred) {
                        return $child->filter($pred);
                    },
                    Lazy::filter(
                        function (self $child) use ($pred) {
                            return call_user_func($pred, $child->getValue());
                        },
                        $this->getChildren()
                    )
                );
            }
        );
    }

    private static function permutations(array $nodes)
    {
        foreach ($nodes as $index => $node) {
            foreach ($node->getChildren() as $child) {
                yield Arrays::assoc($nodes, $index, $child);
            }
        }
    }

    public static function zip(callable $f, $nodes)
    {
        return new self(
            call_user_func_array($f, Lazy::realize(Lazy::map(
                function (self $node) {
                    return $node->getValue();
                },
                $nodes
            ))),
            function () use ($f, $nodes) {
                return Lazy::map(
                    function ($xs) use ($f) {
                        return self::zip($f, $xs);
                    },
                    self::permutations($nodes)
                );
            }
        );
    }

    private static function remove(array $nodes)
    {
        return Lazy::concat(
            Lazy::map(
                function ($index) use ($nodes) {
                    return Arrays::excludeNth($index, $nodes);
                },
                array_keys($nodes)
            ),
            self::permutations($nodes)
        );
    }

    public static function shrink(callable $f, $nodes)
    {
        $nodes = Lazy::realize($nodes);
        if (empty($nodes)) {
            return self::pure(call_user_func($f));
        } else {
            return new self(
                call_user_func_array($f, Lazy::realize(Lazy::map(
                    function (self $node) {
                        return $node->getValue();
                    },
                    $nodes
                ))),
                function () use ($f, $nodes) {
                    return Lazy::map(
                        function ($x) use ($f) {
                            return self::shrink($f, $x);
                        },
                        self::remove($nodes)
                    );
                }
            );
        }
    }
}
