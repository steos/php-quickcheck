<?php


namespace QuickCheck;

class ShrinkResult
{
    private $visited;
    private $depth;
    private $test;

    function __construct(int $visited, int $depth, PropertyTest $test) {
        $this->visited = $visited;
        $this->depth = $depth;
        $this->test = $test;
    }

    function visited() {
        return $this->visited;
    }

    function depth() {
        return $this->depth;
    }

    function test(): PropertyTest {
        return $this->test;
    }

    /**
     * @param ShrinkTreeNode $tree
     * @return \Generator|ShrinkResult[]
     */
    static function searchSmallest(ShrinkTreeNode $tree)
    {
        $nodes = $tree->getChildren();
        $visited = 0;
        $depth = 0;
        for (; $nodes->valid(); $nodes->next(), $visited++) {
            /** @var ShrinkTreeNode $head */
            $head = $nodes->current();
            /** @var PropertyTest $result */
            $result = $head->getValue();
            if (PropertyTest::isFailure($result)) {
                $children = $head->getChildren();
                if (!empty($children)) {
                    $nodes = $children;
                    $depth++;
                }
                yield new ShrinkResult($visited, $depth, $result);
            }
        }
    }
}
