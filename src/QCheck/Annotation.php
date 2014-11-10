<?php

namespace QCheck;

class AnnotationException extends \Exception {}
class MissingTypeAnnotationException extends AnnotationException {}
class AmbiguousTypeAnnotationException extends AnnotationException {}

/**
 * This class contains methods to determine the generator to use
 * based on the type annotation of the tested function / method.
 *
 * @package QCheck
 */
class Annotation {
    /**
     * Return the correct reflection class for the given callable.
     *
     * @param callable $f
     * @throws AnnotationException
     * @return \ReflectionFunction|\ReflectionMethod
     */
    static function getReflection(callable $f) {
        if(is_string($f)) {
            if(strpos($f, '::', 1) !== false) {
                return new \ReflectionMethod($f);
            } else {
                return new \ReflectionFunction($f);
            }
        } else if(is_array($f) && count($f) == 2) {
            return new \ReflectionMethod($f[0], $f[1]);
        }
        throw new AnnotationException("Unable to determine callable type.");
    }

    /**
     * Return the types for the given callable.
     *
     * @param callable $f
     * @throws AnnotationException
     * @return array
     */
    static function types(callable $f) {
        $ref = self::getReflection($f);

        $docs = $ref->getDocComment();
        $proto = $ref->getParameters();

        preg_match_all('/@param\s+(?P<type>.*?)\s+\$(?P<name>.*?)\s+/', $docs, $docs, PREG_SET_ORDER);

        $params = array();
        foreach($proto as $p) {
            $name = $p->getName();
            $type = null;
            foreach($docs as $k => $d) {
                if($d['name'] === $name) {
                    $type = $d['type'];
                    unset($docs[$k]);
                    break;
                }
            }
            if(is_null($type)) {
                throw new MissingTypeAnnotationException("Cannot determine type for $name.");
            }
            if(count(explode('|', $type)) > 1) {
                throw new AmbiguousTypeAnnotationException("Ambiguous type for $name : $type");
            }
            $params[$name] = $type;
        }

        return $params;
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
     * @return array
     */
    static function check(callable $f, callable $p = null, $n = 10) {
        if(is_null($p)) {
            $p = function($result) { return $result === true; };
        }

        $types = self::types($f);

        $args = array();
        $args[] = Generator::alphaStrings();

        $check = function() use($f, $p) {
            $result = call_user_func_array($f, func_get_args());
            return $p($result);
        };

        $prop = Generator::forAll($args, $check);

        return Quick::check($n, $prop);
    }
}