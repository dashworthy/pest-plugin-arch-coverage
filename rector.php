<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

/*
 | The set selection is pestphp/pest-plugin-template's, on this repository's PHP
 | floor rather than the template's 8.1.
 |
 | src/ only: tests/Fixtures holds classes whose exact shape is the thing under
 | test — an abstract method on a trait, an empty final class — and a refactor
 | that "improves" one silently changes what the suite asserts.
 |
 | src/Autoload.php is skipped. ClosureToArrowFunctionRector rewrites the
 | registration into a single arrow expression, and that closure's reflected
 | signature is load bearing: Pest\Expectation::__call matches its return type
 | string exactly to choose the direct-invocation branch. The file is twelve
 | lines of hand-maintained framework adapter; it is not where automated
 | refactoring earns its keep.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
    ])
    ->withSkip([
        __DIR__.'/src/Autoload.php',
    ])
    ->withPhpSets(php84: true)
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
    ]);
