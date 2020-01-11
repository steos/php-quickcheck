# Introduction

Here is a failing example:

```php
$stringsAreNeverNumeric = Property::forAll(
    [Gen::asciiStrings()],
    function($str) {
        return !is_numeric($str);
    }
);

$result = Property::check($stringsAreNeverNumeric, 1000);
$result->dump('json_encode');
```

This will produce something like the following output:

```
Failed after 834 tests
Seed: 1578763578270
Failing input:
["9E70"]
Smallest failing input:
["0"]
```

What this tells us that after 834 random tests the property was sucessfully falsified.
The exact argument that caused the failure was "9E70" which then got shrunk to "0".
So our minimal failing case is the string "0".

Here's another example:

```php
// predicate function that checks if the given
// array elements are in ascending order
function isAscending(array $xs) {
    $last = count($xs) - 1;
    for ($i = 0; $i < $last; ++$i) {
        if ($xs[$i] > $xs[$i + 1]) {
            return false;
        }
    }
    return true;
}

// sort function that is obviously broken
function myBrokenSort(array $xs) {
    return $xs;
}

// so let's test our sort function, it should work on all possible int arrays
$brokenSort = Property::forAll(
    [Gen::ints()->intoArrays()],
    function(array $xs) {
        return isAscending(myBrokenSort($xs));
    }
);

$result = Property::check($brokenSort, 100);
$result->dump('json_encode');
```

This will result in output similar to:

```
Failed after 7 tests
Seed: 1578763516428
Failing input:
[[3,-5,-1,-1,-6]]
Smallest failing input:
[[0,-1]]
```

As you can see the list that failed was `[3,-5,-1,-1,-6]` which got shrunk to `[0,-1]`. For each run
the exact failing values are different but it will always shrink down to `[1,0]` or `[0,-1]`.

The result also contains the seed so you can run the exact same test by passing it as an option
to the check function:

```php
Property::check($brokenSort, 100, ['seed' => 1411398418957])
        ->dump('json_encode');
```

This always fails with the array `[-3,6,5,-5,1]` after 7 tests and shrinks to `[1,0]`.
