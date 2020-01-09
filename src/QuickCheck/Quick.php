<?php

namespace QuickCheck;

use QuickCheck\Generator as Gen;

class Quick
{

    public static function check($n, Gen $prop, array $opts = [])
    {
        $maxSize = @$opts['max_size'] ?: 200;
        $seed = @$opts['seed'] ?: intval(1000 * microtime(true));
        $rng = new Random($seed);
        $sizes = Gen::sizes($maxSize);
        for ($i = 0, $sizes->rewind(); $i < $n; ++$i, $sizes->next()) {
            $size = $sizes->current();
            $resultMapRose = $prop->call($rng, $size);
            $resultMap = $resultMapRose->getRoot();
            $result = $resultMap['result'];
            $args = $resultMap['args'];
            if (!$result || $result instanceof \Exception) {
                if (@$opts['echo']) {
                    echo 'F', PHP_EOL;
                }
                return self::failure($prop, $resultMapRose, $i, $size, $seed);
            }
            if (@$opts['echo']) {
                echo '.';
            }
            // TODO: trial reporting
        }
        return self::complete($prop, $n, $seed);
    }

    public static function complete(Gen $prop, $n, $seed)
    {
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

    public static function failure(Gen $prop, RoseTree $tree, $n, $size, $seed)
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

    public static function shrinkLoop(RoseTree $tree)
    {
        $nodes = FP::realize($tree->getChildren());
        $smallest = $tree->getRoot();
        $visited = 0;
        $depth = 0;
        for ($i = 0; $i < count($nodes); ++$i) {
            $head = $nodes[$i];
            $root = $head->getRoot();
            $result = $root['result'];
            if (!$result || $result instanceof \Exception) {
                $children = FP::realize($head->getChildren());
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
