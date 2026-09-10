<?php

use Pest\Arch\Blueprint;
use Pest\Arch\SingleArchExpectation;
use Pest\Arch\Support\FileLineFinder;
use PHPUnit\Architecture\Elements\ObjectDescription;
use PHPUnit\Architecture\Enums\ObjectType;

it('still exposes the object description shape this package reads', function () {
    expect(property_exists(ObjectDescription::class, 'path'))->toBeTrue()
        ->and(property_exists(ObjectDescription::class, 'type'))->toBeTrue()
        ->and(property_exists(ObjectDescription::class, 'reflectionClass'))->toBeTrue()
        ->and(property_exists(ObjectDescription::class, 'name'))->toBeTrue();
});

it('still exposes exactly the four object kinds the skip list names', function () {
    // The match in src/Autoload.php is exhaustive by design. A fifth case would
    // make it throw at runtime, so it fails here first, where the message says why.
    expect(array_map(fn (ObjectType $type): string => $type->value, ObjectType::cases()))
        ->toEqualCanonicalizing(['class', 'enum', 'trait', 'interface']);
});

it('still exposes the blueprint and expectation entry points', function () {
    expect(method_exists(Blueprint::class, 'make'))->toBeTrue()
        ->and(method_exists(Blueprint::class, 'targeted'))->toBeTrue()
        ->and(method_exists(SingleArchExpectation::class, 'fromExpectation'))->toBeTrue()
        ->and(method_exists(FileLineFinder::class, 'where'))->toBeTrue();
});
