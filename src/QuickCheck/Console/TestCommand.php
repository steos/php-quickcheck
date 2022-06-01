<?php

namespace QuickCheck\Console;

use QuickCheck\Lazy;
use QuickCheck\Property;
use QuickCheck\PropertyTest;
use QuickCheck\Random;
use QuickCheck\ShrinkResult;
use QuickCheck\ShrinkTreeNode;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'test')]
class TestCommand extends Command
{
    protected static $defaultName = 'test';
    private $numTests;
    private $seed;
    private $maxSize;
    private $testCount;

    protected function configure()
    {
        $this->addArgument('property', InputArgument::REQUIRED, 'Property to run')
            ->addOption(
                'tests',
                't',
                InputOption::VALUE_REQUIRED,
                'Number of tests to run',
                100
            )->addOption(
                'max-size',
                'x',
                InputOption::VALUE_REQUIRED,
                'Maximum size',
                200
            )->addOption(
                'seed',
                's',
                InputOption::VALUE_REQUIRED,
                'Number of tests to run'
            );
    }

    static function encode($xs) {
        return var_export($xs, true);//json_encode($x, JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_IGNORE);
    }

    private function executeTests(Property $property, OutputInterface $output) {
        $testProgress = new ProgressBar($output);
        $testProgress->setBarWidth(50);
        $testProgress->start($this->numTests);

        $startTime = microtime(true);
        $rng = new Random($this->seed);
        /** @var ShrinkTreeNode[] $tests */
        $tests = Lazy::take($this->numTests, $property->randomTests($rng));
        /** @var ShrinkTreeNode|null $failure */
        $failure = null;
        $this->testCount = 0;
        foreach ($tests as $test) {
            $this->testCount++;
            $testProgress->advance();
            if (PropertyTest::isFailure($test->getValue())) {
                $failure = $test;
                break;
            }
        }

        $elapsed = microtime(true) - $startTime;
        $testProgress->setProgress($this->testCount);
        $testProgress->display();
        $output->writeLn('');
        $output->writeLn('');

        $mem = memory_get_peak_usage(true)/(1024*1024);
        $output->writeLn(sprintf("Time: %d ms, Memory: %.2f MB, Seed: %d", $elapsed * 1000, $mem, $this->seed));
        $output->writeLn('');
        return $failure;
    }

    private function executeShrinkSearch(ShrinkTreeNode $failure, OutputInterface $output)
    {
        $fmt = $this->getHelper('formatter');
        /** @var PropertyTest $result */
        $result = $failure->getValue();
        $output->writeLn("<bg=red;fg=white>QED. ($this->testCount tests)</>");
        $output->write('Failing inputs: ');
        $output->writeLn($fmt->truncate(self::encode($result->arguments()), 5000));
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
                $progress->writeLn($fmt->truncate(self::encode($shrinkResult->test()->arguments()), 5000));
            }
            $smallest = $shrinkResult;
        }
        $info->overwrite(sprintf('Shrinking inputs...done. (%.2f s)', microtime(true) - $shrinkStart));
        $progress->clear();
        $output->write('Smallest failing inputs: ');
        $output->writeLn($fmt->truncate(self::encode($smallest->test()->arguments()), 5000));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (function_exists('xdebug_is_enabled')) {
            ini_set('xdebug.max_nesting_level', '999999');
            $output->writeLn('<bg=yellow;fg=black>Warning: xdebug is enabled. This has a high performance impact.</>');
        }

        $propertyFile = $input->getArgument('property');
        $this->numTests = $input->getOption('tests');
        $this->maxSize = $input->getOption('max-size')  ?: 200;
        $this->seed = $input->getOption('seed') ?: intval(1000 * microtime(true));

        $property = require $propertyFile;

        if (!$property instanceof Property) {
            $output->writeLn("$propertyFile does not contain a QuickCheck\\Property");
            return 1;
        }

        $output->writeLn(sprintf('%s %s Don\'t write tests. Generate them.',
            $this->getApplication()->getName(),
            $this->getApplication()->getVersion()));
        $output->writeLn('');

        $failure = $this->executeTests($property, $output);

        if ($failure === null) {
            $output->writeLn("<bg=green;fg=black>Success ($this->testCount tests)</>");
            return 0;
        }

        $this->executeShrinkSearch($failure, $output);

        return 1;
    }
}