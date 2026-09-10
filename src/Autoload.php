<?php

declare(strict_types=1);

use Dashworthy\PestPluginArchCoverage\CoverageInspector;
use Dashworthy\PestPluginArchCoverage\CoverageMap;
use Dashworthy\PestPluginArchCoverage\Rule;
use Pest\Arch\Contracts\ArchExpectation;

/*
 | The registration, and nothing else. Every decision lives in CoverageInspector,
 | which is a class with typed parameters that static analysis and unit tests can
 | both reach; this file cannot be either, which is why it is kept this thin and
 | is excluded in phpstan.neon.
 |
 | The closure MUST declare ": ArchExpectation" as its return type.
 | Pest\Expectation::__call inspects that exact return type to decide whether to
 | invoke the closure directly; without it the call is routed through
 | ExpectationPipeline and the lazy arch evaluation never runs. That same branch
 | forwards the call's arguments — "return $closure(...$parameters);" on the same
 | line — which is how this verb takes its map as a parameter rather than reading
 | a published config file.
 |
 | $map:    source directory => one or more test directories. Any one candidate
 |          satisfies the class; longest matching source root wins.
 | $suffix: appended to a source basename to form its test's basename.
 | $skip:   structural kinds needing no test. Null uses all four.
 | $exempt: FQCNs whose descendants and implementors are exempt. Naming a class
 |          here does not exempt that class itself.
 */
expect()->extend('toHaveTests', function (
    array $map,
    string $suffix = 'Test',
    ?array $skip = null,
    array $exempt = [],
): ArchExpectation {
    return Rule::make($this, new CoverageInspector(
        CoverageMap::make($map, $suffix),
        $skip ?? CoverageInspector::DEFAULT_SKIP,
        $exempt,
    ));
});
