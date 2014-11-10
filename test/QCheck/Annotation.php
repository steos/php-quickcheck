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
     * @dataProvider getFaultyMethods
     * @expectedException QCheck\AmbiguousTypeAnnotationException
     */
    function testAmbiguousType($function) {
        Annotation::types(self::getCallable($function));
    }
}