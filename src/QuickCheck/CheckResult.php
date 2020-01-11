<?php

namespace QuickCheck;

interface CheckResult
{
    function seed(): int;
    function numTests(): int;
    function isFailure(): bool;
    function isSuccess(): bool;
}
