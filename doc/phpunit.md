## PHPUnit

To use PHPQuickCheck with PHPUnit, the assertion `\QuickCheck\PHPUnit\PropertyConstraint` is provided.
It provides a static constructor method `PropertyConstraint::check`.
Similar to `Property::check`, the method takes the size and allows also passing the seed if needed.

```php
public function testStringsAreLessThanTenChars()
{
    $property = Property::forAll(
        [Gen::strings()],
        fn ($s): bool => 10 > strlen($s)
    );

    $this->assertThat($property, PropertyConstraint::check(50)); // will fail
}
```

The assertion will delegate to `Property::check($size, $seed)`, and if the function returns anything but `true`, it will display a formatted failure description.

```txt
Failed asserting that property is true.
Tests runs: 16, failing size: 15, seed: 1578486446175, smallest shrunk value(s):
array (
  0 => <failed shrunk value>,
)
```

If an exception is thrown or a PHPUnit assertion fails, the message will be included in the output.

To reproduce a test result the displayed seed can be passed via `PropertyConstraint::check($size, 1578486446175)`.
