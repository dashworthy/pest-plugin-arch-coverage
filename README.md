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

Given an action at `app/Domains/Billing/Actions/CreateInvoice.php`:

```php
namespace App\Domains\Billing\Actions;

use App\Domains\Billing\Models\Invoice;

final class CreateInvoice
{
    public function handle(int $teamId, int $amountCents): Invoice
    {
        return Invoice::create([
            'team_id' => $teamId,
            'amount_cents' => $amountCents,
            'status' => 'pending',
        ]);
    }
}
```

this expectation demands a test at
`tests/Unit/Domains/Billing/Actions/CreateInvoiceTest.php`, and fails naming that
exact path until it exists:

```php
arch('every action has a unit test')
    ->expect('App\Domains')
    ->toHaveTests([
        app_path('Domains') => base_path('tests/Unit/Domains'),
    ]);
```

The map is an argument, not configuration, so two conventions can disagree about
where a test belongs:

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
