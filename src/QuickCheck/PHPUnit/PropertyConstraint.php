<?php

namespace QuickCheck\PHPUnit;

use PHPUnit\Framework\Constraint\Constraint;
use QuickCheck\Failure;
use QuickCheck\Property;

class PropertyConstraint extends Constraint
{
    /**
     * @var int
     */
    private $numTests;

    /**
     * @var int
     */
    private $seed;

    public function __construct(int $n, int $seed = null)
    {
        $this->numTests = $n;
        $this->seed = $seed;
    }

    public static function check($n = 100, int $seed = null)
    {
        return new self($n, $seed);
    }

    public function evaluate($prop, string $description = '', bool $returnResult = false)
    {
        $result = Property::check($prop, $this->numTests, $this->seed);
        return parent::evaluate($result, $description, $returnResult);
    }

    protected function matches($other): bool
    {
        return $other->isSuccess();
    }

    public function toString(): string
    {
        return 'property is true';
    }

    protected function failureDescription($other): string
    {
        return $this->toString();
    }

    /**
     * @param Failure $failure
     * @return string
     */
    protected function additionalFailureDescription($failure): string
    {
        return sprintf(
            "%sTest runs: %d, seed: %s, smallest shrunk value(s):\n%s",
            $this->extractExceptionMessage($failure->test()->result()),
            $failure->numTests(),
            $failure->seed(),
            var_export($failure->shrunk()->test()->arguments(), true)
        );
    }

    private function extractExceptionMessage($result): string
    {
        return $result instanceof \Exception ? $result->getMessage() . "\n" : '';
    }

}
