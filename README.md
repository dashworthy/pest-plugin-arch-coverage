# pest-plugin-arch-coverage

One architecture expectation for Pest: every class under a mapped source
directory must have a test file at the mirrored position.

The motivating case is generated code. An agent told by a *failing test* that its
action needs a test writes one; an agent told so by a *document* does not. So the
failure names the paths that would satisfy it rather than restating the rule.

## Installation

```bash
composer require --dev dashworthy/pest-plugin-arch-coverage
```

The expectation registers itself through Composer's `autoload.files`. There is
nothing to publish and no service provider.

## Usage

The verb takes a whole **layer** — every class beneath a source root — and
requires each to have a test at the mirrored position. It does not single out a
kind of class: an action, a model, and a data object under the root are all
subject to it equally. There is no "Actions directory" to point at; you map the
root, and every class below it is covered.

Point it at a namespace, and the map mirrors each matched class into a test root
at the same relative path. A class at
`App\Domains\Platform\Billing\Actions\PublishPlanVersion` requires a test at
`tests/Unit/Domains/Platform/Billing/Actions/PublishPlanVersionTest.php`, and the
failure names that exact path until it exists:

```php
arch('every domain class has a mirrored test')
    ->expect('App\Domains')
    ->toHaveTests([
        app_path('Domains') => base_path('tests/Unit/Domains'),
    ]);
```

### Sending one layer to a different suite

The map is an argument, not a config file, and the **longest matching source
root wins**. That lets one layer inside the tree mirror somewhere else without
restating its parent — here, controllers run through the framework, so they
belong in the feature suite while everything else stays in the unit suite:

```php
arch('every domain class has a mirrored test')
    ->expect('App\Domains')
    ->toHaveTests([
        app_path('Domains') => base_path('tests/Unit/Domains'),
        app_path('Domains/Platform/Billing/Controllers')
            => base_path('tests/Feature/Domains/Platform/Billing/Controllers'),
    ]);
```

A controller matches both roots; the longer one wins, so its test is required
under `tests/Feature/Domains/...` and not in the unit suite. A single global
config listing roots could not express "this subtree, but not its parent".

## Signature

```php
toHaveTests(
    array $map,
    string $suffix = 'Test',
    ?array $skip = null,
    array $exempt = [],
)
```

| Parameter | Meaning |
|---|---|
| `$map` | `source directory => test directory`, or `=> [several]`. Any one candidate satisfies the class. Longest matching source root wins, so a subtree can point at a different test root without restating its parent. |
| `$suffix` | Appended to the source basename to form the test basename. |
| `$skip` | Structural kinds needing no test: `'abstract'`, `'interface'`, `'trait'`, `'enum'`. `null` means all four. Pass a shorter list to demand tests for a kind; pass `[]` to demand them for all. |
| `$exempt` | FQCNs whose **descendants and implementors** are exempt. Naming a class here does not exempt that class itself. |

Directories rather than namespaces, because a Pest test file declares no class:
`class_exists()` is false for every test in a Pest suite, so whether a test exists
is a question about a file.

## Failures

```
Expecting a test for this class, but it has no corresponding test. Create
/app/tests/Unit/Domains/Users/CreateUserActionTest.php. Or, if it is genuinely
untestable, name a base class in the exempt list, or exclude it with ->ignoring(...).

at app/Domains/Users/CreateUserAction.php:9
```

One violation is reported per run. `Pest\Arch\Blueprint::targeted()` throws inside
its own loop, so a convention failing across forty classes reports the first and
you rerun for the next. Recovering aggregation would mean replacing `Blueprint`,
which is a cost this package deliberately does not pay.

## Deliberately not included

- **The reverse direction** — "this test file covers a class that no longer
  exists". Its subject is a Pest test file, and a Pest test file declares no
  class, so it never becomes an object arch can reason about at all.
- **Symlink detection beneath a test root.** It existed to protect a walk of the
  test tree, and there is no such walk in the forward direction. Roots reached
  through a symlink are still resolved, so a package symlinked into `vendor/`
  compares equal to the same directory named literally.
- **A config file.** Every parameter is an argument at the call site.

## Suppression

`pest-plugin-arch` honours `@pest-arch-ignore-line` and
`@pest-arch-ignore-next-line` unconditionally, and that cannot be disabled from
outside `Blueprint`. If suppression matters to you, police it with an ordinary
arch test:

```php
arch('no source file suppresses an architecture rule')
    ->expect('App')
    ->not->toUse('@pest-arch-ignore-line');
```

## Licence

MIT.
