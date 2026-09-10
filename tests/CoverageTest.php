<?php

use Dashworthy\PestPluginArchCoverage\Tests\Fixtures\Coverage\App\AbstractAction;
use Dashworthy\PestPluginArchCoverage\Tests\Fixtures\Coverage\App\Concerns\ActionTrait;
use Dashworthy\PestPluginArchCoverage\Tests\Fixtures\Coverage\App\Contracts\ActionContract;
use Dashworthy\PestPluginArchCoverage\Tests\Fixtures\Coverage\App\CoveredAction;
use Dashworthy\PestPluginArchCoverage\Tests\Fixtures\Coverage\App\ExemptModel;
use Dashworthy\PestPluginArchCoverage\Tests\Fixtures\Coverage\App\SecondRootAction;
use Dashworthy\PestPluginArchCoverage\Tests\Fixtures\Coverage\App\Status;
use Dashworthy\PestPluginArchCoverage\Tests\Fixtures\Coverage\App\UncoveredAction;
use Dashworthy\PestPluginArchCoverage\Tests\Fixtures\Coverage\Outside\StrayAction;
use Dashworthy\PestPluginArchCoverage\Tests\Fixtures\Coverage\Support\ExemptBase;
use PHPUnit\Framework\AssertionFailedError;

function coverageFixture(string $directory): string
{
    return __DIR__.'/Fixtures/Coverage/'.$directory;
}

/**
 * @return array<string, array<int, string>>
 */
function coverageRoots(): array
{
    return [coverageFixture('App') => [coverageFixture('Feature'), coverageFixture('Unit')]];
}

it('passes a class covered in the first test root', function () {
    expect(CoveredAction::class)->toHaveTests(coverageRoots(), 'Spec')
        ->ensureLazyExpectationIsVerified();

    expect(true)->toBeTrue();
});

it('passes a class covered in the second test root', function () {
    expect(SecondRootAction::class)->toHaveTests(coverageRoots(), 'Spec')
        ->ensureLazyExpectationIsVerified();

    expect(true)->toBeTrue();
});

it('reports a class with no test', function () {
    expect(fn () => expect(UncoveredAction::class)
        ->toHaveTests(coverageRoots(), 'Spec')
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'has no corresponding test');
});

it('names every candidate path in the failure', function () {
    $run = fn () => expect(UncoveredAction::class)
        ->toHaveTests(coverageRoots(), 'Spec')
        ->ensureLazyExpectationIsVerified();

    expect($run)->toThrow(AssertionFailedError::class, 'Feature/UncoveredActionSpec.php');
    expect($run)->toThrow(AssertionFailedError::class, 'Unit/UncoveredActionSpec.php');
});

it('accepts only the roots the map names', function () {
    // The controller/action split: SecondRootAction is covered in Unit only, so
    // a call demanding a Feature test must report it.
    expect(fn () => expect(SecondRootAction::class)
        ->toHaveTests([coverageFixture('App') => coverageFixture('Feature')], 'Spec')
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'has no corresponding test');
});

it('defaults the suffix to Test', function () {
    expect(fn () => expect(CoveredAction::class)
        ->toHaveTests(coverageRoots())
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'CoveredActionTest.php');
});

it('skips an abstract class, an interface, a trait and an enum by default', function () {
    foreach ([AbstractAction::class, ActionContract::class, ActionTrait::class, Status::class] as $skipped) {
        expect($skipped)->toHaveTests(coverageRoots(), 'Spec')->ensureLazyExpectationIsVerified();
    }

    expect(true)->toBeTrue();
});

it('reports an abstract class once abstract is dropped from the skip list', function () {
    expect(fn () => expect(AbstractAction::class)
        ->toHaveTests(coverageRoots(), 'Spec', [])
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'has no corresponding test');
});

it('reports an interface, a trait and an enum once their kind is dropped from the skip list', function () {
    // Ordering guard: an interface that declares a method and a trait that
    // declares an abstract one both report isAbstract() === true, so a skip list
    // naming only 'abstract' must not silence either of them.
    foreach ([ActionContract::class, ActionTrait::class, Status::class] as $reported) {
        expect(fn () => expect($reported)
            ->toHaveTests(coverageRoots(), 'Spec', ['abstract'])
            ->ensureLazyExpectationIsVerified())
            ->toThrow(AssertionFailedError::class, 'has no corresponding test');
    }
});

it('skips a descendant of an exempt ancestor', function () {
    expect(ExemptModel::class)
        ->toHaveTests(coverageRoots(), 'Spec', null, [ExemptBase::class])
        ->ensureLazyExpectationIsVerified();

    expect(true)->toBeTrue();
});

it('reports a class named in the exempt list on its own account', function () {
    // The exempt list names ancestors. Naming an uncovered class directly must
    // not silence it, or the list becomes a general-purpose mute button.
    expect(fn () => expect(UncoveredAction::class)
        ->toHaveTests(coverageRoots(), 'Spec', null, [UncoveredAction::class])
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'has no corresponding test');
});

it('reports a class under no mapped source root', function () {
    expect(fn () => expect(StrayAction::class)
        ->toHaveTests(coverageRoots(), 'Spec')
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'is not under any mapped source root');
});

it('refuses an empty map', function () {
    expect(fn () => expect(CoveredAction::class)
        ->toHaveTests([])
        ->ensureLazyExpectationIsVerified())
        ->toThrow(InvalidArgumentException::class, 'coverage map is empty');
});
