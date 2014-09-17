<?php

namespace QCheck;

use QCheck\Generator as Gen;
use QCheck\Random;
use QCheck\RoseTree;

class Quick {

    static function check($n, Generator $prop, array $opts = []) {
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
                return self::failure($prop, $resultMapRose, $i, $size, $seed);
            }
            // TODO: trial reporting
        }
        return self::complete($prop, $n, $seed);
    }

    static function complete(Generator $prop, $n, $seed) {
        return ['result' => true,
                'num_tests' => $n,
                'seed' => $seed];
    }

    static function smallestShrink($visited, $depth, $smallest) {
        return ['nodes_visited' => $visited,
                'depth' => $depth,
                'result' => $smallest['result'],
                'smallest' => $smallest['args']];
    }

    static function failure(Generator $prop, RoseTree $tree, $n, $size, $seed) {
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

    static function shrinkLoop(RoseTree $tree) {
        $nodes = $tree->getChildren();
        $smallest = $tree->getRoot();
        $visited = 0;
        $depth = 0;
        for ($nodes->rewind(); $nodes->valid();) {
            $head = $nodes->current();
            $nodes->next();
            $root = $head->getRoot();
            $result = $root['result'];
            if (!$result || $result instanceof \Exception) {
                $children = $head->getChildren();
                if (empty($children)) {
                    $smallest = $root;
                    $visited++;
                } else {
                    $nodes = $children;
                    $nodes->rewind();
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
