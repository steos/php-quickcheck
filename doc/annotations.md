## Automatic detection for Generators

The `Annotation` class provide a helper to automatically use a generator based
on the documented types for the function:

```
/**
 * @param string $s
 * @return bool
 */
function my_function($s) {
    return is_string($s);
}

Annotation::check('my_function');
```

This will test `my_function` using the `Generator::strings()` generator.

You can also register your own Generators using the
`Annotation::register($type, $generator)` method.
