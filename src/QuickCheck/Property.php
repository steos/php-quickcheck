<?php

namespace QuickCheck;

use QuickCheck\Generator as Gen;

class Property
{
    /** @var Generator */
    private $generator;

    private function __construct(Gen $generator)
    {
        $this->generator = $generator;
    }

    public static function forAll(array $args, callable $f)
    {
        return new self(Gen::tuples($args)->fmap(function ($args) use ($f) {
            try {
                $result = call_user_func_array($f, $args);
            } catch (\Exception $e) {
                $result = $e;
            }
            return ['result' => $result,
                'function' => $f,
                'args' => $args];
        }));
    }
    public function check($n, array $opts = [])
    {
        $maxSize = @$opts['max_size'] ?: 200;
        $seed = @$opts['seed'] ?: intval(1000 * microtime(true));
        $rng = new Random($seed);
        $sizes = Gen::sizes($maxSize);
        for ($i = 0, $sizes->rewind(); $i < $n; ++$i, $sizes->next()) {
            $size = $sizes->current();
            $resultMapRose = $this->generator->call($rng, $size);
            $resultMap = $resultMapRose->getRoot();
            $result = $resultMap['result'];
            $args = $resultMap['args'];
            if (!$result || $result instanceof \Exception) {
                if (@$opts['echo']) {
                    echo 'F', PHP_EOL;
                }
                return self::failure($resultMapRose, $i, $size, $seed);
            }
            if (@$opts['echo']) {
                echo '.';
            }
            // TODO: trial reporting
        }
        return ['result' => true,
            'num_tests' => $n,
            'seed' => $seed];
    }

    public static function smallestShrink($visited, $depth, $smallest)
    {
        return ['nodes_visited' => $visited,
                'depth' => $depth,
                'result' => $smallest['result'],
                'smallest' => $smallest['args']];
    }

    public static function failure(ShrinkTree $tree, $n, $size, $seed)
    {
        $root = $tree->getRoot();
        $result = $root['result'];
        $args = $root['args'];
        // TODO failure reporting
        return ['result' => $result,
                'seed' => $seed,
                'failing_size' => $size,
                'num_tests' => $n + 1,
                'fail' => $args,
                'shrunk' => self::shrinkLoop($tree)];
    }

    public static function shrinkLoop(ShrinkTree $tree)
    {
        $nodes = Lazy::realize($tree->getChildren());
        $smallest = $tree->getRoot();
        $visited = 0;
        $depth = 0;
        for ($i = 0; $i < count($nodes); ++$i) {
            $head = $nodes[$i];
            $root = $head->getRoot();
            $result = $root['result'];
            if (!$result || $result instanceof \Exception) {
                $children = Lazy::realize($head->getChildren());
                if (empty($children)) {
                    $smallest = $root;
                    $visited++;
                } else {
                    $nodes = $children;
                    $i = -1;
                    $smallest = $root;
                    $visited++;
                    $depth++;
                }
            } else {
                $visited++;
            }
        }
        return self::smallestShrink($visited, $depth, $smallest);
    }
}
