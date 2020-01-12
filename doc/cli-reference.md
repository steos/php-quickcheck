# CLI Reference

Usage:
```
quickcheck [ FILE | DIR ] [OPTIONS]
```

The first argument must be a file or directory.

Examples:

```
quickcheck test
quickcheck test/example.php
quickcheck test/example.php -t 1000 -s 123
```

Options:

```
-t  number of tests to run
-x  maximum size
-s  RNG seed
```

