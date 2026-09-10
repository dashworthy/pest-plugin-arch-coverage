---
name: enforcing-test-coverage
description: Use when adding or changing an arch test that demands test coverage of a source tree in a project that has dashworthy/pest-plugin-arch-coverage installed — the package ships one expectation, toHaveTests(), which asserts that every class under a mapped source directory has a test file at the mirrored position, and this skill explains how to map the directories, how to scope or exempt a class, and what its failure is telling you to do. Not for writing the tests themselves.
---

# Enforcing test coverage with an arch expectation

This project has `dashworthy/pest-plugin-arch-coverage` installed. It registers
one expectation, `toHaveTests()`, onto Pest's architecture plugin. It composes
with any `arch()` chain — there is no separate DSL.

## Write the test

```php
arch('every action has a unit test')
    ->expect('App\Domains')
    ->toHaveTests([
        app_path('Domains') => base_path('tests/Unit/Domains'),
    ]);
```

The map is an **argument**, not configuration, which is what lets two
conventions disagree about where a test belongs:

```php
arch('controllers have feature tests')
    ->expect('App\Http\Controllers')
    ->toHaveTests([app_path('Http/Controllers') => base_path('tests/Feature/Http')]);

arch('actions have unit tests')
    ->expect('App\Domains')
    ->toHaveTests([app_path('Domains') => base_path('tests/Unit/Domains')]);
```

Neither satisfies the other. A single global config listing both roots could not
express that.

## The signature

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
| `$skip` | Structural kinds needing no test: `'abstract'`, `'interface'`, `'trait'`, `'enum'`. `null` means all four. Pass a shorter list to demand tests for a kind; pass `[]` to demand them for every kind. |
| `$exempt` | FQCNs whose **descendants and implementors** are exempt. |

Directories rather than namespaces, because a Pest test file declares no class:
`class_exists()` is false for every test in a Pest suite, so whether a test
exists is a question about a file.

## Reading a failure

```
Expecting a test for this class, but it has no corresponding test. Create
tests/Unit/Domains/Users/CreateUserActionTest.php. Or, if it is genuinely
untestable, name a base class in the exempt list, or exclude it with ->ignoring(...).
```

**The fix is to create the test at the named path.** That is the whole reason
the message names it. Do NOT add the class to the `$exempt` list to silence
this, and do NOT add a `@pest-arch-ignore-line` annotation.

The other message — *"it is not under any mapped source root"* — is about the
`arch()` test, not the class: the layer selects classes the map does not cover.
Widen the map, or narrow the layer with `->ignoring(...)`.

## Before you exempt something

`$exempt` names **ancestors**. Naming a class there does not exempt that class
itself — it must be reached through a parent or an interface. That asymmetry is
deliberate: it stops the list becoming a general-purpose mute button.

An `->ignoring(...)` records only that a class was skipped, never why. Prefer a
base class in `$exempt` that says what the category is.

## Three behaviours to know

- **One violation per run.** `Blueprint` throws on the first failing class, so a
  tree with forty uncovered classes takes forty passes. Expected; write the
  test, rerun, repeat.
- **An empty layer passes.** A typo'd namespace turns this rule green. If that
  matters, assert the layer is non-empty in a separate test.
- **`@pest-arch-ignore-line` suppresses it**, and cannot be disabled from
  outside `Blueprint`. Police its use with an arch test of your own if that
  matters.

## What it does not do

There is no reverse direction — nothing asserts that a test file still covers a
class that exists. A Pest test file declares no class, so it never becomes an
object arch can reason about.
