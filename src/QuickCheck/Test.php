<?php

namespace QuickCheck;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class Test
{
    private $testCount = 0;

    private static $currentSuite = null;
    /** @var CheckSuite[] */
    private static $suites = [];

    static function forAll($args, $pred, $times = 100, $maxSize = 200) {
        $check = new Check(self::$currentSuite);
        $check->times($times)
            ->maxSize($maxSize)
            ->forAll($args, $pred);
    }

    static function check(string $name = null) {
        return new Check(self::$currentSuite, $name);
    }

    static private function readOpts(array $argv) {
        $n = count($argv);
        $args = [];
        $opts = [];
        $current = &$args;
        for ($i = 0; $i < $n; ++$i) {
            if ($argv[$i][0] === '-') {
                $name = substr($argv[$i], 1);
                $opts[$name] = [];
                $current = &$opts[$name];
            } else {
                $current[] = $argv[$i];
            }
        }
        return [$args, array_map(function($opt) {
            return @$opt[0] ?? true;
        }, $opts)];
    }

    static private function printUsage() {
        echo "Usage: quickcheck [ FILE | DIRECTORY ] [OPTIONS]", PHP_EOL;
    }

    static private function loadSuite($file) {
        self::$currentSuite = new CheckSuite(basename($file, '.php'));
        require_once $file;
        if (!self::$currentSuite->empty()) {
            self::$suites[] = self::$currentSuite;
        }
    }

    private static function loadSuites($args) {
        self::$suites = [];
        self::$currentSuite = null;

        if (is_dir($args[0])) {
            $it = new \DirectoryIterator($args[0]);
            foreach ($it as $file) {
                if ($file->isDot() || $file->getExtension() !== 'php') continue;
                self::loadSuite($file->getRealPath());
            }
        } elseif (is_file($args[0])) {
            self::loadSuite($args[0]);
        } else {
            echo "Error: \"$args[0]\" is not a valid directory or file.", PHP_EOL;
            self::printUsage();
            exit(1);
        }
    }

    static function main($argv) {
        if (count($argv) < 2) {
            self::printUsage();
            exit(0);
        }
        [$args, $opts] = self::readOpts(array_slice($argv, 1));

        self::loadSuites($args);

        $seed = intval(@$opts['s'] ?? 1000 * microtime(true));

        $out = new ConsoleOutput();
        $test = new Test();

        $out->writeLn("PHPQuickCheck 2.0.0-dev. Don't write tests. Generate them.");
        if (function_exists('xdebug_is_enabled')) {
            ini_set('xdebug.max_nesting_level', '999999');
            $out->writeLn('<bg=yellow;fg=black>Warning: xdebug is enabled. This has a high performance impact.</>');
        }
        $out->writeLn('');

        if (count(self::$suites) === 1 && count(self::$suites[0]->checks()) === 1) {
            $check = self::$suites[0]->checks()[0];
            $prop = $check->property();
            $numTests = intval(@$opts['t'] ?? $check->numTests());
            $maxSize = intval(@$opts['x'] ?? $prop->maxSize());
            $failure = $test->execute($out, $seed, $numTests, $prop->withMaxSize($maxSize));
            if ($failure === null) {
                $out->writeLn("<bg=green;fg=black>Success ($test->testCount tests)</>");
                exit(0);
            }
            $test->executeShrinkSearch($failure, $out);
            $out->writeLn("\n<bg=red;fg=white>QED. ($test->testCount tests)</>");
            exit(1);
        } else {
            $propCount = 0;
            $totalTestCount = 0;
            $start = microtime(true);
            $failures = 0;
            foreach (self::$suites as $suite) {
                $singleCheck = count($suite->checks()) === 1;
                if (!$singleCheck) {
                    $out->writeLn($suite->file());
                }
                foreach ($suite->checks() as $check) {
                    $propCount++;
                    $name = $check->name() ?? $suite->file();
                    if ($singleCheck) {
                        $out->write(sprintf('%\'.-50s', $name));
                    } else {
                        $out->write(sprintf('  %\'.-48s', $name));
                    }
                    $failure = $test->execute($out, $seed, $check->numTests(), $check->property(), true);
                    $totalTestCount += $test->testCount;
                    if ($failure === null) {
                        $out->writeLn("<bg=green;fg=black> OK </> $test->testCount");
                    } else {
                        $failures++;
                        $out->writeLn("<bg=red;fg=white> QED. ($test->testCount tests) </>\n");
                        $test->executeShrinkSearch($failure, $out);
                        $out->writeLn('');
                    }
                }
            }
            $elapsed = microtime(true) - $start;
            $mem = memory_get_peak_usage(true)/(1024*1024);
            $out->writeLn(sprintf("\nTime: %d ms, Memory: %.2f MB, Seed: %d\n",
                $elapsed * 1000, $mem, $seed));

            if ($failures === 0) {
                $out->writeLn("<bg=green;fg=black>Success ($propCount properties, $totalTestCount tests)</>");
                exit(0);
            } else {
                $out->writeLn("<bg=red;fg=white>FAILURES! ($failures failed, $propCount properties, $totalTestCount tests)</>");
                exit(1);
            }
        }
    }

    private function execute(ConsoleOutput $out, int $seed, int $numTests, Property $property, bool $silent = false) {
        $testProgress = new ProgressBar($out);
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

            $out->writeLn('');
            $out->writeLn('');

            $mem = memory_get_peak_usage(true)/(1024*1024);
            $out->writeLn(sprintf("Time: %d ms, Memory: %.2f MB, Seed: %d, maxSize: %d",
                $elapsed * 1000, $mem, $seed, $property->maxSize()));
            $out->writeLn('');
        }

        return $failure;
    }

    private static function encode($xs, $lineLimit = 20) {
        //json_encode($xs, JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_IGNORE);
        $str = var_export($xs, true);
        $lines = explode("\n", $str);
        $lineCount = count($lines);
        $linesOut = array_slice($lines, 0, $lineLimit);
        if ($lineCount > $lineLimit) {
            $omitted = $lineCount - $lineLimit;
            $linesOut[] = "\n<bg=cyan;fg=black><{$omitted} more lines have been omitted></>\n";
        }
        return implode("\n", $linesOut);
    }

    private function executeShrinkSearch(ShrinkTreeNode $failure, ConsoleOutput $output)
    {
        /** @var PropertyTest $result */
        $result = $failure->getValue();
        $output->write('Failing inputs: ');
        $output->writeLn(self::encode($result->arguments()));
        $output->writeLn('');

        $info = $output->section();
        $info->writeLn('Shrinking inputs...');

        $shrinkStart = microtime(true);
        /** @var ShrinkResult $smallest */
        $smallest = new ShrinkResult(0, 0, $result);
        $lastShrinkProgressUpdate = 0;
        $progress = $output->section();
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
                $progress->writeLn(self::encode($shrinkResult->test()->arguments()));
            }
            $smallest = $shrinkResult;
        }
        $info->overwrite(sprintf('Shrinking inputs...done. (%.2f s)', microtime(true) - $shrinkStart));
        $progress->clear();
        $output->write('Smallest failing inputs: ');
        $output->writeLn(self::encode($smallest->test()->arguments()));
    }

}