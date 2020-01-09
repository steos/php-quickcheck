# Generators

```php
// integers
Gen::ints()->takeSamples();
# => [0,1,-1,3,-2,0,3,3,-8,9]
```

```php
// ascii strings
Gen::asciiStrings()->takeSamples();
# => ["","O","@","&5h","QSuC","","c[,","[Q","  ){#o{Z","\"!F=n"]
```

```php
// arrays
Gen::ints()->intoArrays()->takeSamples();
# => [[],[],[-2],[0,0],[2],[4,-4,-1,2],[],[-5,-7,3,-2,2,0,-2], ...]
```

```php
// randomly choose between multiple generators
Gen::oneOf(Gen::booleans(), Gen::ints())->takeSamples();
# => [false,true,false,false,2,true,true,3,3,2]
```

```php
// randomly choose element from array
Gen::elements('foo', 'bar', 'baz')->takeSamples();
# => ["foo","foo","bar","bar","foo","foo","bar","baz","foo","baz"]
```

```php
// tuples of generators
Gen::tuples(Gen::posInts(), Gen::alphaNumStrings())->takeSamples();
# => [[0,""],[1,""],[0,"86"],[3,"NPG"],[4,"1q"],[4,"6"],[1,"Eu60MN"],[1,"6q9D8wm"], ...]
```

```php
// notEmpty - generated value must not be empty
Gen::arraysOf(Gen::ints())->notEmpty()->takeSamples();
# => [[0],[1],[1,1],[4,-1],[1],[0,0,1,1,-2],[-6,5,4,-2,-1],[3,7,-2],[-6,5],[-1,1,-4]]
```

```php
// suchThat - generated value must pass given predicate function
// Note: Only do this if you can't generate the value deterministically. SuchThat may fail
// if the generator doesn't return an acceptable value after 10 times.
// (you can pass $maxTries as second argument if necessary).
$oddInts = Gen::ints()->suchThat(function($i) {
    return $i % 2 != 0;
});
$oddInts->takeSamples();
# => [1,1,-1,-3,5,5,-3,-7,1,7]
```

```php
// frequency - 1/3 of the time generate posInts, 2/3 of the time booleans
Gen::frequency(1, Gen::posInts(), 2, Gen::booleans())->takeSamples();
# => [0,1,false,true,true,false,false,true,5,false]
```

```php
// fmap - transform the value generated
Gen::posInts()->fmap(function($i) { return $i * 2; })->takeSamples();
# => [0,2,4,2,4,8,10,8,0,6]
```

```php
// use fmap to generate day times
$daytimes = Gen::tuples(Gen::choose(0, 23), Gen::choose(0, 59))
    ->fmap(function($daytime) {
        list($h, $m) = $daytime;
        return sprintf('%02d:%02d', $h, $m);
    });

$daytimes->takeSamples();
# => ["03:35","17:03","23:31","17:50","08:41","23:07","17:59","05:10","03:47","09:36"]
```

```php
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
```

```php
// maps from string keys to daytimes
$daytimes->mapsFrom(Gen::alphaStrings()->notEmpty())->notEmpty()->takeSamples();
# => [{"v":"08:36"},{"i":"03:43","E":"14:38"},{"gUU":"03:08","UIc":"19:24"}, ...]
```

```php
// maps from strings to booleans
Gen::alphaStrings()->notEmpty()->mapsTo(Gen::booleans())->notEmpty()->takeSamples();
# => [{"J":true},{"pt":true,"TgQ":false},{"M":true,"jL":false},{"rm":true}, ...]
```

```php
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
