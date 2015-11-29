<?php

namespace QCheck;

/**
 * @param string $a
 * @return bool
 */
function _annotation_test_function($a) {
    return is_string($a);
}

class _AnnotationInvokeTestClass {
    /**
     * @param string $a
     * @return bool
     */
    public function __invoke($a) {
        return is_string($a);
    }
}

class _TestClass { }

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

    /**
     * @param int $b
     * @param array $c
     * @param string $a
     */
    static function static_method($a, $b, $c) {}

    static function nodoc($a) {}

    /**
     * @param $a
     */
    static function faultydoc($a) {}

    /**
     * @param string $a
     */
    static function incompletedoc($a, $b) {}

    /**
     * @param string|int $a
     */
    static function ambiguousdoc($a) {}

    /**
     * @param some_type $a
     */
    static function nogenerator($a) {}

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
     * @param _TestClass $a
     * @return bool
     */
    static function custom_type(_TestClass $a) {
        return is_object($a) && $a instanceof _TestClass;
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
    static $test = null;
    static $fun = null;
    static $invoke = null;

    static function setUpBeforeClass() {
        self::$test = new _AnnotationTestClass();

        /**
         * @param string $a
         * @return bool
         */
        self::$fun = function($a) {
            return is_string($a);
        };

        self::$invoke = new _AnnotationInvokeTestClass();
    }

    static function getNamespace() {
        return 'QCheck\_AnnotationTestClass';
    }

    static function getArrayCallable($function) {
        return array(self::getNamespace(), $function);
    }

    static function getStringCallable($function) {
        return self::getNamespace().'::'.$function;
    }

    static function getObjectCallable($function) {
        return array(self::$test, $function);
    }

    function testTypeByName() {
        $type = Annotation::types('QCheck\_annotation_test_function');
        $this->assertEquals($type, array('a' => 'string'));
    }

    function testCheckByName() {
        $result = Annotation::check('QCheck\_annotation_test_function');
        $this->assertTrue($result['result']);
    }

    function testTypeByVariable() {
        $type = Annotation::types(self::$fun);
        $this->assertEquals($type, array('a' => 'string'));
    }

    function testCheckByVariable() {
        $result = Annotation::check(self::$fun);
        $this->assertTrue($result['result']);
    }

    function testTypeByInvoke() {
        $type = Annotation::types(self::$invoke);
        $this->assertEquals($type, array('a' => 'string'));
    }

    function testCheckByInvoke() {
        $result = Annotation::check(self::$invoke);
        $this->assertTrue($result['result']);
    }

    function testStatic() {
        $function = 'static_method';
        $array = Annotation::types(self::getArrayCallable($function));
        $string = Annotation::types(self::getStringCallable($function));
        $object = Annotation::types(self::getObjectCallable($function));

        $types = array('a' => 'string', 'b' => 'int', 'c' => 'array');
        $this->assertEquals($array, $types);
        $this->assertEquals($string, $types);
        $this->assertEquals($object, $types);
    }

    function getMethods() {
        return array(
            array('str', array('a' => 'string')),
            array('int', array('a' => 'int')),
            array('arr', array('a' => 'array')),
            array('str_str_str', array('a' => 'string', 'b' => 'string', 'c' => 'string')),
            array('str_int_arr', array('a' => 'string', 'b' => 'int', 'c' => 'array')),
            array('reverse', array('a' => 'string', 'b' => 'int', 'c' => 'array')),
            array('static_method', array('a' => 'string', 'b' => 'int', 'c' => 'array')),
        );
    }

    /**
     * @dataProvider getMethods
     */
    function testType($function, $types) {
        $object = Annotation::types(self::getObjectCallable($function));
        $this->assertEquals($object, $types);
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
     * @expectedException QCheck\Exceptions\MissingTypeAnnotationException
     */
    function testNoTypeString($function) {
        Annotation::types(self::getStringCallable($function));
    }

    /**
     * @dataProvider getFaultyMethods
     * @expectedException QCheck\Exceptions\MissingTypeAnnotationException
     */
    function testNoTypeArray($function) {
        Annotation::types(self::getArrayCallable($function));
    }

    /**
     * @dataProvider getFaultyMethods
     * @expectedException QCheck\Exceptions\MissingTypeAnnotationException
     */
    function testNoTypeObject($function) {
        Annotation::types(self::getObjectCallable($function));
    }

    function getAmbiguousMethods() {
        return array(
            array('ambiguousdoc'),
        );
    }

    /**
     * @dataProvider getAmbiguousMethods
     * @expectedException QCheck\Exceptions\AmbiguousTypeAnnotationException
     */
    function testAmbiguousTypeString($function) {
        Annotation::types(self::getStringCallable($function));
    }

    /**
     * @dataProvider getAmbiguousMethods
     * @expectedException QCheck\Exceptions\AmbiguousTypeAnnotationException
     */
    function testAmbiguousTypeArray($function) {
        Annotation::types(self::getArrayCallable($function));
    }

    /**
     * @dataProvider getAmbiguousMethods
     * @expectedException QCheck\Exceptions\AmbiguousTypeAnnotationException
     */
    function testAmbiguousTypeObject($function) {
        Annotation::types(self::getObjectCallable($function));
    }

    function getNoGeneratorMethods() {
        return array(
            array('nogenerator'),
        );
    }

    /**
     * @dataProvider getNoGeneratorMethods
     * @expectedException QCheck\Exceptions\NoGeneratorAnnotationException
     */
    function testNoGeneratorTypeString($function) {
        Annotation::check(self::getStringCallable($function));
    }

    /**
     * @dataProvider getNoGeneratorMethods
     * @expectedException QCheck\Exceptions\NoGeneratorAnnotationException
     */
    function testNoGeneratorTypeArray($function) {
        Annotation::check(self::getArrayCallable($function));
    }

    /**
     * @dataProvider getNoGeneratorMethods
     * @expectedException QCheck\Exceptions\NoGeneratorAnnotationException
     */
    function testNoGeneratorTypeObject($function) {
        Annotation::check(self::getObjectCallable($function));
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
        $object = Annotation::check(self::getObjectCallable($function));
        $this->assertTrue($object['result']);
    }

    function testRegister() {
        $generator = Generator::any()->fmap(function() { return new _TestClass(); });
        Annotation::register('_TestClass', $generator);

        $array = Annotation::check(self::getArrayCallable('custom_type'), null, 1);
        $string = Annotation::check(self::getStringCallable('custom_type'), null, 1);
        $object = Annotation::check(self::getObjectCallable('custom_type'), null, 1);

        $this->assertTrue($array['result']);
        $this->assertTrue($string['result']);
        $this->assertTrue($object['result']);
    }

    /**
     * @expectedException QCheck\Exceptions\DuplicateGeneratorException
     */
    function testDuplicateRegister() {
        $generator = Generator::any()->fmap(function() { return new _TestClass(); });
        Annotation::register('_TestClass', $generator);
        Annotation::register('_TestClass', $generator);
    }
}
