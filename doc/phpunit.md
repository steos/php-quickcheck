## PHPUnit

To use php-quickcheck with PHPUnit, the assertion `\QuickCheck\PHPUnit\Constraint\Prop` is provided.
It provides a static constructor method `Prop::check`. Similar to `Quick::check`, the method takes the size and allows also passing options if needed.

```php
public function testStringsAreLessThanTenChars()
{
    $property = Gen::forAll([Gen::strings()], function ($s): bool {
        return 10 > strlen($s);
    });
    $this->assertThat($property, Prop::check(50)); // will fail
}
```

The assertion will delegate to `Quick::check($size, $property)`, and if the function returns anything but `true`, it will display a formatted failure description.

```
Failed asserting that property is true.
Tests runs: 16, failing size: 15, seed: 1578486446175, smallest shrunk value(s):
array (
  0 => <failed shrunk value>,
)
```

If an exception is thrown or a PHPUnit assertion fails, the message will be included in the output.

To reproduce a test result the displayed seed can be passed via `Prop::check($size, ['seed' => 1578486446175])`.
