<?php

namespace QuickCheck\Console;

use Symfony\Component\Console\Application;

class App {
    static function main() {
        $app = new Application('PHPQuickCheck', '2.0.0-dev');
        $test = new TestCommand();
        $app->add($test);
        $app->run();
    }
}
