# PhpQuickCheck

PhpQuickCheck is a generative testing library for PHP based on
clojure.test.check.

> Don't write tests. Generate them.
> - John Hughes

## Huh?

Generative testing, also called property-based testing, is about
describing the behaviour of your system in terms of properties that
should hold true for all possible input.

### Examples

Here is a failing example:

```php
use QCheck\Generator as Gen;
use QCheck\Quick;

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

### Generators

```php
// integers
Gen::ints()->takeSamples();
# => [0,1,-1,3,-2,0,3,3,-8,9]

// ascii strings
Gen::asciiStrings()->takeSamples();
# => ["","O","@","&5h","QSuC","","c[,","[Q","  ){#o{Z","\"!F=n"]

// arrays
Gen::ints()->intoArrays()->takeSamples();
# => [[],[],[-2],[0,0],[2],[4,-4,-1,2],[],[-5,-7,3,-2,2,0,-2], ...]

// randomly choose between multiple generators
Gen::oneOf(Gen::booleans(), Gen::ints())->takeSamples();
# => [false,true,false,false,2,true,true,3,3,2]

// randomly choose element from array
Gen::elements('foo', 'bar', 'baz')->takeSamples();
# => ["foo","foo","bar","bar","foo","foo","bar","baz","foo","baz"]

// tuples of generators
Gen::tuples(Gen::posInts(), Gen::alphaNumStrings())->takeSamples();
# => [[0,""],[1,""],[0,"86"],[3,"NPG"],[4,"1q"],[4,"6"],[1,"Eu60MN"],[1,"6q9D8wm"], ...]

// notEmpty - generated value must not be empty
Gen::arraysOf(Gen::ints())->notEmpty()->takeSamples();
# => [[0],[1],[1,1],[4,-1],[1],[0,0,1,1,-2],[-6,5,4,-2,-1],[3,7,-2],[-6,5],[-1,1,-4]]

// suchThat - generated value must pass given predicate function
// Note: Only do this if you can't generate the value deterministically. SuchThat may fail
// if the generator doesn't return an acceptable value after 10 times.
// (you can pass $maxTries as second argument if necessary).
$oddInts = Gen::ints()->suchThat(function($i) {
    return $i % 2 != 0;
});
$oddInts->takeSamples();
# => [1,1,-1,-3,5,5,-3,-7,1,7]

// frequency - 1/3 of the time generate posInts, 2/3 of the time booleans
Gen::frequency(1, Gen::posInts(), 2, Gen::booleans())->takeSamples();
# => [0,1,false,true,true,false,false,true,5,false]

// fmap - transform the value generated
Gen::posInts()->fmap(function($i) { return $i * 2; })->takeSamples();
# => [0,2,4,2,4,8,10,8,0,6]

// use fmap to generate day times
$daytimes = Gen::tuples(Gen::choose(0, 23), Gen::choose(0, 59))
    ->fmap(function($daytime) {
        list($h, $m) = $daytime;
        return sprintf('%02d:%02d', $h, $m);
    });

$daytimes->takeSamples();
# => ["03:35","17:03","23:31","17:50","08:41","23:07","17:59","05:10","03:47","09:36"]

// bind - create new generator based on generated value
$bindSample = Gen::ints()->intoArrays()->notEmpty()
    ->bind(function($ints) {
        // choose one random element from the int array
        return Gen::elements($ints)
        // and return a tuple of the whole array and the chosen value
        ->fmap(function($i) use ($ints) {
            return [$ints, $i];
        });
    });
$bindSample->takeSamples();
# => [..., [[4,-2,5,3,5],4],[[-3,5,5,6,2],5],[[1,-3,1,5,2],-3], ...]

// maps from string keys to daytimes
$daytimes->mapsFrom(Gen::alphaStrings()->notEmpty())->notEmpty()->takeSamples();
# => [{"v":"08:36"},{"i":"03:43","E":"14:38"},{"gUU":"03:08","UIc":"19:24"}, ...]

// maps from strings to booleans
Gen::alphaStrings()->notEmpty()->mapsTo(Gen::booleans())->notEmpty()->takeSamples();
# => [{"J":true},{"pt":true,"TgQ":false},{"M":true,"jL":false},{"rm":true}, ...]

// maps with fixed keys
Gen::mapsWith([
    'age'       => Gen::choose(18, 99),
    'name'      => Gen::tuples(
                       Gen::elements('Ada', 'Grace', 'Hedy'),
                       Gen::elements('Lovelace', 'Hopper', 'Lamarr')
                   )->fmap(function($name) {
                       return "$name[0] $name[1]";
                   })
])->takeSamples();
# => [{"age":50,"name":"Ada Lamarr"},{"age":97,"name":"Ada Hopper"},
#     {"age":81,"name":"Ada Lovelace"},{"age":55,"name":"Hedy Lamarr"}, ...]
```

## Project Status

PhpQuickCheck is highly experimental and in its very early stages. Only
the core functionality has been implemented so far. At the moment the
project is mostly still in a proof-of-concept stage and there is
obviously no documentation whatsoever.

### Contributing

All suggestions, contributions and flames are welcome.

Feel free to fork and send a pull request. If you intend to make
major changes please get in touch so we can coordinate our efforts.

## Credits

All credit goes to clojure.test.check, this project is mostly just a
port.

## Requirements

Requires PHP 5.5.x with 64 bit integers and gmp extension.

## License

Copyright © 2014 Stefan Oestreicher.

Distributed under the terms of the BSD (3-Clause) license.

