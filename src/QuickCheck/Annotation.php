<?php

namespace QuickCheck;

use QuickCheck\Exceptions\AmbiguousTypeAnnotationException;
use QuickCheck\Exceptions\AnnotationException;
use QuickCheck\Exceptions\DuplicateGeneratorException;
use QuickCheck\Exceptions\MissingTypeAnnotationException;
use QuickCheck\Exceptions\NoGeneratorAnnotationException;

/**
 * This class contains methods to determine the generator to use
 * based on the type annotation of the tested function / method.
 *
 * @package QuickCheck
 */
class Annotation
{
    /**
     * @var array types associated with generators
     */
    private static $generators = array(
        'bool' => 'booleans',
    );

    /**
     * Return the correct reflection class for the given callable.
     *
     * @param callable $f
     * @throws AnnotationException
     * @return \ReflectionFunction|\ReflectionMethod
     */
    public static function getReflection(callable $f)
    {
        if (is_string($f)) {
            if (strpos($f, '::', 1) !== false) {
                return new \ReflectionMethod($f);
            } else {
                return new \ReflectionFunction($f);
            }
        } elseif (is_array($f) && count($f) == 2) {
            return new \ReflectionMethod($f[0], $f[1]);
        } elseif ($f instanceof \Closure) {
            return new \ReflectionFunction($f);
        } elseif (is_object($f) && method_exists($f, '__invoke')) {
            return new \ReflectionMethod($f, '__invoke');
        }
        // if the tests above are exhaustive, we should never hit the next line.
        throw new AnnotationException("Unable to determine callable type.");
    }

    /**
     * Return the types for the given callable.
     *
     * @param callable $f
     * @throws AnnotationException
     * @return array
     */
    public static function types(callable $f)
    {
        $ref = self::getReflection($f);

        $docs = $ref->getDocComment();
        $proto = $ref->getParameters();

        preg_match_all('/@param\s+(?P<type>.*?)\s+\$(?P<name>.*?)\s+/', $docs, $docs, PREG_SET_ORDER);

        $params = array();
        foreach ($proto as $p) {
            $name = $p->getName();
            $type = null;
            foreach ($docs as $k => $d) {
                if ($d['name'] === $name) {
                    $type = $d['type'];
                    unset($docs[$k]);
                    break;
                }
            }
            if (is_null($type)) {
                throw new MissingTypeAnnotationException("Cannot determine type for $name.");
            }
            if (count(explode('|', $type)) > 1) {
                throw new AmbiguousTypeAnnotationException("Ambiguous type for $name : $type");
            }
            $params[$name] = $type;
        }

        return $params;
    }

    /**
     * Associate a generator to a given type.
     *
     * @param string $type
     * @param Generator $generator Tĥe generator associated with the type
     * @throws DuplicateGeneratorException
     */
    public static function register($type, Generator $generator)
    {
        if (array_key_exists($type, self::$generators)) {
            throw new DuplicateGeneratorException("A generator is already registred for $type.");
        }

        self::$generators[$type] = $generator;
    }

    /**
     * Determine the generators needed to test the function $f and then
     * use the predicate $p to assert correctness.
     *
     * If $p is omitted, will simply check that $f returns true for
     * each generated values.
     *
     * @param callable $f The function to test
     * @param callable $p The predicate
     * @param int $n number of iteration
     * @throws NoGeneratorAnnotationException
     * @return CheckResult
     */
    public static function check(callable $f, callable $p = null, $n = 10)
    {
        if (is_null($p)) {
            $p = function ($result) {
                return $result === true;

            };
        }

        $types = self::types($f);

        $args = array();
        foreach ($types as $t) {
            $array = false;
            if (substr($t, -2) == '[]') {
                $t = substr($t, 0, -2);
                $array = true;
            }

            if (array_key_exists($t, self::$generators)) {
                $generator = self::$generators[$t];
            } elseif (method_exists('QuickCheck\Generator', $t.'s')) {
                $generator = $t.'s';
            } else {
                throw new NoGeneratorAnnotationException("Unable to find a generator for $t");
            }

            if (! $generator instanceof Generator) {
                $generator = call_user_func(array('QuickCheck\Generator', $generator));
            }

            if ($array) {
                $generator = $generator->intoArrays();
            }
            $args[] = $generator;
        }

        $check = function () use ($f, $p) {
            $result = call_user_func_array($f, func_get_args());
            return $p($result);
        };

        return Property::check(Property::forAll($args, $check), $n);
    }
}
