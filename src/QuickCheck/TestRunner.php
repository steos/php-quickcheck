<?php

namespace QuickCheck;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class TestRunner
{
    private $numTests;
    private $maxSize;
    private $out;
    private $testCount;

    function __construct(int $numTests = null, int $maxSize = null) {
        $this->numTests = $numTests;
        $this->maxSize = $maxSize;
        $this->out = new ConsoleOutput();
    }

    /**
     * @param CheckSuite[] $suites
     * @param int $seed
     */
    function execute(array $suites, int $seed) {
        $this->writeLn("PHPQuickCheck 2.0.2. Don't write tests. Generate them.");
        if (function_exists('xdebug_is_enabled')) {
            ini_set('xdebug.max_nesting_level', '999999');
            $this->writeLn('<bg=yellow;fg=black>Warning: xdebug is enabled. This has a high performance impact.</>');
        }
        $this->writeLn();

        $this->testCount = 0;
        if (count($suites) === 1 && count($suites[0]->checks()) === 1) {
            $this->executeSingleSuiteCheck($suites[0]->checks()[0], $seed);
        } else {
            $this->executeSuiteChecks($suites, $seed);
        }
    }

    private function executeSingleSuiteCheck(Check $check, int $seed) {
        $prop = $check->property();
        $numTests = $this->numTests ?? $check->numTests();
        $maxSize = $this->maxSize ?? $prop->maxSize();
        $failure = $this->executeTests($seed, $numTests, $prop->withMaxSize($maxSize));
        if ($failure === null) {
            $this->writeLn("<bg=green;fg=black>Success ($this->testCount tests)</>");
            exit(0);
        }
        $this->executeShrinkSearch($failure);
        $this->writeLn("\n<bg=red;fg=white>QED. ($this->testCount tests)</>");
        exit(1);
    }

    private function executeSuiteChecks(array $suites, int $seed) {
        $propCount = 0;
        $totalTestCount = 0;
        $start = microtime(true);
        $failures = 0;
        foreach ($suites as $suite) {
            $singleCheck = count($suite->checks()) === 1;
            if (!$singleCheck) {
                $this->writeLn($suite->file());
            }
            foreach ($suite->checks() as $check) {
                $propCount++;
                $name = $check->name() ?? $suite->file();
                if ($singleCheck) {
                    $this->write(sprintf('%\'.-50s', $name));
                } else {
                    $this->write(sprintf('  %\'.-48s', $name));
                }
                $numTests = $this->numTests ?? $check->numTests();
                $maxSize = $this->maxSize ?? $check->property()->maxSize();
                $failure = $this->executeTests($seed, $numTests, $check->property()->withMaxSize($maxSize), true);
                $totalTestCount += $this->testCount;
                if ($failure === null) {
                    $this->writeLn("<bg=green;fg=black> OK </> t = $this->testCount, x = $maxSize");
                } else {
                    $failures++;
                    $this->writeLn("<bg=red;fg=white> QED. ($this->testCount tests) </> x = $maxSize\n");
                    $this->executeShrinkSearch($failure);
                    $this->writeLn();
                }
            }
        }
        $elapsed = microtime(true) - $start;
        $mem = memory_get_peak_usage(true)/(1024*1024);
        $this->writeLn(sprintf("\nTime: %d ms, Memory: %.2f MB, Seed: %d\n",
            $elapsed * 1000, $mem, $seed));

        if ($failures === 0) {
            $this->writeLn("<bg=green;fg=black>Success ($propCount properties, $totalTestCount tests)</>");
            exit(0);
        } else {
            $this->writeLn("<bg=red;fg=white>FAILURES! ($failures failed, $propCount properties, $totalTestCount tests)</>");
            exit(1);
        }
    }

    private function executeTests(int $seed, int $numTests, Property $property, bool $silent = false) {
        $testProgress = new ProgressBar($this->out);
        if (!$silent) {
            $testProgress->setBarWidth(50);
            $testProgress->start($numTests);
        }

        $startTime = microtime(true);
        $rng = new Random($seed);
        /** @var ShrinkTreeNode[] $tests */
        $tests = Lazy::take($numTests, $property->randomTests($rng));
        /** @var ShrinkTreeNode|null $failure */
        $failure = null;
        $this->testCount = 0;
        foreach ($tests as $test) {
            $this->testCount++;
            if (!$silent) {$testProgress->advance();}
            if (PropertyTest::isFailure($test->getValue())) {
                $failure = $test;
                break;
            }
        }

        $elapsed = microtime(true) - $startTime;
        if (!$silent) {
            $testProgress->setProgress($this->testCount);
            $testProgress->display();

            $this->writeLn('');
            $this->writeLn('');

            $mem = memory_get_peak_usage(true)/(1024*1024);
            $this->writeLn(sprintf("Time: %d ms, Memory: %.2f MB, Seed: %d, maxSize: %d",
                $elapsed * 1000, $mem, $seed, $property->maxSize()));
            $this->writeLn('');
        }

        return $failure;
    }

    private function formatTestArguments(PropertyTest $result, $lineLimit = 10, $wiggle = 5) {
        $str = var_export($result->arguments(), true);
        //json_encode($xs, JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_IGNORE);
        $lines = explode("\n", $str);
        $lineCount = count($lines);
        $linesOut = array_slice($lines, 0, $lineLimit);
        if ($lineCount > $lineLimit + $wiggle) {
            $omitted = $lineCount - $lineLimit;
            $linesOut[] = "\n<{$omitted} more lines have been omitted>\n";
        }
        return implode("\n", $linesOut);
    }

    private function writeLn($msg = null) {
        $this->out->writeLn($msg ?? '');
    }

    private function write(string $msg) {
        $this->out->write($msg);
    }

    private function executeShrinkSearch(ShrinkTreeNode $failure)
    {
        /** @var PropertyTest $result */
        $result = $failure->getValue();
        $this->write('<bg=yellow;fg=black>Failing inputs:</> ');
        $this->writeLn($this->formatTestArguments($result));
        $this->writeLn();

        $info = $this->out->section();
        $info->writeLn('<bg=magenta;fg=black>Shrinking inputs...</>');

        $shrinkStart = microtime(true);
        /** @var ShrinkResult $smallest */
        $smallest = new ShrinkResult(0, 0, $result);
        $lastShrinkProgressUpdate = 0;
        $progress = $this->out->section();
        foreach (ShrinkResult::searchSmallest($failure) as $shrinkResult) {
            $now = microtime(true);
            $shrinkElapsed = $now - $shrinkStart;
            if ($now - $lastShrinkProgressUpdate > 1) {
                $lastShrinkProgressUpdate = $now;
                $throughput = $shrinkResult->visited() / $shrinkElapsed;
                $progress->clear();
                $progress->writeLn(sprintf('visited: %d, depth: %d, memory: %.2f MB, throughput: %d visits/s',
                    $shrinkResult->visited(),
                    $shrinkResult->depth(),
                    memory_get_usage() / (1024 * 1024),
                    $throughput));
                $progress->write('Current smallest failing inputs: ');
                $progress->writeLn($this->formatTestArguments($shrinkResult->test()));
            }
            $smallest = $shrinkResult;
        }
        $info->overwrite(sprintf('Shrinking inputs...done. (%.2f s)', microtime(true) - $shrinkStart));
        $progress->clear();
        $this->write('<bg=cyan;fg=black>Smallest failing inputs:</> ');
        $this->writeLn($this->formatTestArguments($smallest->test()));
    }
}