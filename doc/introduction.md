# Introduction

Here is a failing example:

```php
use QuickCheck\Generator as Gen;
use QuickCheck\Quick;

$stringsAreNeverNumeric = Gen::forAll(
    [Gen::asciiStrings()],
    function($str) {
        return !is_numeric($str);
    }
);

$result = Quick::check(1000, $stringsAreNeverNumeric);
var_dump($result);
```

This will produce something like the following output (json encoded for readability):

```
"result": false,
"seed": 1411306705536,
"failing_size": 109,
"num_tests": 110,
"fail": [
    "727"
],
"shrunk": {
    "nodes_visited": 15,
    "depth": 4,
    "result": false,
    "smallest": [
        "0"
    ]
}
```

What this tells us that after 110 random tests the property was sucessfully falsified.
The exact argument that caused the failure was "727" which then got shrunk to "0".
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
$brokenSort = Gen::forAll(
    [Gen::ints()->intoArrays()],
    function(array $xs) {
        return isAscending(myBrokenSort($xs));
    }
);

var_dump(Quick::check(100, $brokenSort, ['echo' => true]));

```

This will result in output similar to:

```
......F
{
    "result": false,
    "seed": 1411398418957,
    "failing_size": 6,
    "num_tests": 7,
    "fail": [
        [-3,6,5,-5,1]
    ],
    "shrunk": {
        "nodes_visited": 24,
        "depth": 7,
        "result": false,
        "smallest": [
            [1,0]
        ]
    }
}

```

As you can see the list that failed was `[-3,6,5,-5,1]` which got shrunk to `[1,0]`. For each run
the exact failing values are different but it will always shrink down to `[1,0]` or `[0,-1]`.

The result also contains the seed so you can run the exact same test by passing it as an option
to the check function:

```php
var_dump(Quick::check(100, $brokenSort, ['seed' => 1411398418957]));
```

This always fails with the array `[-3,6,5,-5,1]` after 7 tests and shrinks to `[1,0]`.
