<?php

namespace QCheck;

class DataProviderTest extends \PHPUnit_Framework_TestCase {
    function provider($name, $n = 10) {
        return DataProvider::provider(array(
            Generator::strings(),
            Generator::ints(),
            Generator::booleans()
        ), $n);
    }

    /**
     * @dataProvider provider
     */
    function testDataProvider($s, $i, $b) {
        // this test is only supposed to prove that we have a valid provider
        $this->assertTrue(is_string($s));
        $this->assertTrue(is_int($i));
        $this->assertTrue(is_bool($b));
    }

    function testDataProviderCount() {
        $numbers = array(1, 10, 50);

        foreach($numbers as $n) {
            $data = $this->provider('testDataProviderCount', $n);
            $this->assertCount($n, $data);
            $this->assertCount(3, $data[0]);
        }
    }
}