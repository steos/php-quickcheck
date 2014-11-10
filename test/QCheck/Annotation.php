<?php

namespace QCheck;

class _AnnotationTestClass {
    /**
     * @param string $a
     */
    function str($a) {}

    /**
     * @param int $a
     */
    function int($a) {}


    /**
     * @param array $a
     */
    function arr($a) {}

    /**
     * @param string $a
     * @param string $b
     * @param string $c
     */
    function str_str_str($a, $b, $c) {}

    /**
     * @param string $a
     * @param int $b
     * @param array $c
     */
    function str_int_arr($a, $b, $c) {}

    /**
     * @param int $b
     * @param array $c
     * @param string $a
     */
    function reverse($a, $b, $c) {}

    function nodoc($a) {}

    /**
     * @param $a
     */
    function faultydoc($a) {}

    /**
     * @param string $a
     */
    function incompletedoc($a, $b) {}

    /**
     * @param string|int $a
     */
    function ambiguousdoc($a) {}

    /**
     * @param string $a
     * @return bool
     */
    function check_str($a) {
        return is_string($a);
    }

    /**
     * @param int $a
     * @return bool
     */
    function check_int($a) {
        return is_int($a);
    }

    /**
     * @param bool $a
     * @return bool
     */
    function check_bool($a) {
        return is_bool($a);
    }

    /**
     * @param boolean $a
     * @return bool
     */
    function check_boolean($a) {
        return is_bool($a);
    }

    /**
     * @param string[] $a
     * @return bool
     */
    function check_array($a) {
        if(! is_array($a)) {
            return false;
        }
        foreach($a as $s) {
            if(! is_string($s)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param bool $a
     * @param string $b
     * @param int $c
     * @param int[] $d
     * @return bool
     */
    function check_multiple($a, $b, $c, $d) {
        return is_bool($a) &&
            is_string($b) &&
            is_int($c) &&
            is_array($d) &&
            (count($d) == 0 || is_int($d[0]));
    }
}

class AnnotationTest extends \PHPUnit_Framework_TestCase {
    static function getCallable($function) {
        return array('QCheck\_AnnotationTestClass', $function);
    }

    function getMethods() {
        return array(
            array('str', array('a' => 'string')),
            array('int', array('a' => 'int')),
            array('arr', array('a' => 'array')),
            array('str_str_str', array('a' => 'string', 'b' => 'string', 'c' => 'string')),
            array('str_int_arr', array('a' => 'string', 'b' => 'int', 'c' => 'array')),
            array('reverse', array('a' => 'string', 'b' => 'int', 'c' => 'array')),
        );
    }

    /**
     * @dataProvider getMethods
     */
    function testType($function, $types) {
        $result = Annotation::types(self::getCallable($function));
        $this->assertEquals($result, $types);
    }

    function getFaultyMethods() {
        return array(
            array('nodoc'),
            array('faultydoc'),
            array('incompletedoc'),
        );
    }

    /**
     * @dataProvider getFaultyMethods
     * @expectedException QCheck\MissingTypeAnnotationException
     */
    function testNoType($function) {
        Annotation::types(self::getCallable($function));
    }

    function getAmbiguousMethods() {
        return array(
            array('ambiguousdoc'),
        );
    }

    /**
     * @dataProvider getAmbiguousMethods
     * @expectedException QCheck\AmbiguousTypeAnnotationException
     */
    function testAmbiguousType($function) {
        Annotation::types(self::getCallable($function));
    }

    function getCheckMethods() {
        return array(
            array('check_str'),
            array('check_int'),
            array('check_bool'),
            array('check_boolean'),
            array('check_array'),
            array('check_multiple'),
        );
    }

    /**
     * @dataProvider getCheckMethods
     */
    function testCheck($function) {
        $res = Annotation::check(self::getCallable($function));
        $this->assertTrue($res['result']);
    }
}