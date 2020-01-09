<?php

namespace QuickCheck\PHPUnit;

use PHPUnit\Framework\Constraint\Constraint;

class PropertyConstraint extends Constraint
{
    /**
     * @var int
     */
    private $size;
    
    /**
     * @var array
     */
    private $opts;

    public function __construct(int $n, array $opts = [])
    {
        $this->size = $n;
        $this->opts = $opts;
    }
    
    public static function check($n = 100, array $opts = [])
    {
        return new self($n, $opts);
    }

    public function evaluate($prop, string $description = '', bool $returnResult = false)
    {
        $result = $prop->check($this->size, $this->opts);
        return parent::evaluate($result, $description, $returnResult);
    }

    protected function matches($other): bool
    {
        return @$other['result'] === true;
    }

    public function toString(): string
    {
        return 'property is true';
    }

    protected function failureDescription($other): string
    {
        return $this->toString();
    }

    protected function additionalFailureDescription($other): string
    {
        return sprintf(
            "%sTests runs: %d, failing size: %d, seed: %s, smallest shrunk value(s):\n%s",
            $this->extractExceptionMessage($other['result']),
            $other['num_tests'],
            $other['failing_size'],
            $other['seed'],
            var_export($other['shrunk']['smallest'], true)
        );
    }

    private function extractExceptionMessage($result): string
    {
        return $result instanceof \Exception ? $result->getMessage() . "\n" : '';
    }

}
