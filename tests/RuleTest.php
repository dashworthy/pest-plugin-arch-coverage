<?php

use Dashworthy\PestPluginArchCoverage\Rule;
use Dashworthy\PestPluginArchCoverage\Tests\Fixtures\Support\AlwaysFails;
use Pest\Arch\Exceptions\ArchExpectationFailedException;
use PHPUnit\Architecture\Elements\ObjectDescription;
use PHPUnit\Framework\AssertionFailedError;

it('passes when the inspection finds nothing', function () {
    Rule::make(expect(AlwaysFails::class), fn (ObjectDescription $object): ?string => null)
        ->ensureLazyExpectationIsVerified();

    expect(true)->toBeTrue();
});

it('reports the message the inspection returned', function () {
    expect(fn () => Rule::make(
        expect(AlwaysFails::class),
        fn (ObjectDescription $object): ?string => "create 'some/expected/path.php'",
    )->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, "create 'some/expected/path.php'");
});

it('can name the object it was given in the message', function () {
    expect(fn () => Rule::make(
        expect(AlwaysFails::class),
        fn (ObjectDescription $object): ?string => 'offending class was '.$object->name,
    )->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, AlwaysFails::class);
});

it('points the violation at the class declaration', function () {
    try {
        Rule::make(
            expect(AlwaysFails::class),
            fn (ObjectDescription $object): ?string => 'failed',
        )->ensureLazyExpectationIsVerified();
    } catch (ArchExpectationFailedException $e) {
        $frame = $e->toCollisionEditor();
        $source = explode("\n", (string) file_get_contents((string) $frame->getFile()));

        expect($source[$frame->getLine() - 1] ?? '')->toContain('class AlwaysFails');

        return;
    }

    $this->fail('The rule did not fail.');
});
