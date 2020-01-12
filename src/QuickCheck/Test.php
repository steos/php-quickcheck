<?php

namespace QuickCheck;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class Test
{
    private static $currentSuite = null;
    /** @var CheckSuite[] */
    private static $suites = [];

    static function forAll($args, $pred, $times = 100, $maxSize = 200) {
        self::ensureSuite();
        $check = new Check(self::$currentSuite);
        $check->times($times)
            ->maxSize($maxSize)
            ->forAll($args, $pred);
    }

    static function check(string $name = null) {
        self::ensureSuite();
        return new Check(self::$currentSuite, $name);
    }

    static private function ensureSuite() {
        if (self::$currentSuite === null) {
            throw new \RuntimeException('no active suite');
        }
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

    static private function requireSuiteFile($name, $file) {
        self::$currentSuite = new CheckSuite($name);
        require_once $file;
        if (!self::$currentSuite->empty()) {
            self::$suites[] = self::$currentSuite;
        }
    }

    private static function loadSuites($args) {
        self::$suites = [];
        self::$currentSuite = null;

        if (is_dir($args[0])) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($args[0]));
            $real = realpath($args[0]);
            foreach ($it as $file) {
                if ($file->getExtension() !== 'php') continue;
                $name = substr($file->getRealPath(), strlen($real) + 1, -4);
                self::requireSuiteFile($name, $file->getRealPath());
            }
        } elseif (is_file($args[0])) {
            self::requireSuiteFile(basename($args[0], '.php'), $args[0]);
        } else {
            echo "Error: \"$args[0]\" is not a valid directory or file.", PHP_EOL;
            self::printUsage();
            exit(1);
        }
    }

    static function main(array $argv) {
        if (count($argv) < 2) {
            self::printUsage();
            exit(0);
        }
        [$args, $opts] = self::readOpts(array_slice($argv, 1));
        self::loadSuites($args);
        $seed = intval(@$opts['s'] ?? 1000 * microtime(true));
        $testRunner = new TestRunner(
            @$opts['t'] ? intval($opts['t']) : null,
            @$opts['x'] ? intval($opts['x']) : null
        );
        $testRunner->execute(self::$suites, $seed);
    }
}