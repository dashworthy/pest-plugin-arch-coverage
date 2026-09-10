<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchCoverage;

use Pest\Arch\Blueprint;
use Pest\Arch\Collections\Dependencies;
use Pest\Arch\Contracts\ArchExpectation;
use Pest\Arch\Exceptions\ArchExpectationFailedException;
use Pest\Arch\Options\LayerOptions;
use Pest\Arch\SingleArchExpectation;
use Pest\Arch\Support\FileLineFinder;
use Pest\Arch\ValueObjects\Targets;
use Pest\Arch\ValueObjects\Violation;
use Pest\Expectation;
use PHPUnit\Architecture\Elements\ObjectDescription;

/**
 * Builds an arch expectation whose failure message can name the paths that
 * would satisfy it.
 *
 * Pest's own Targeted::make captures its message as a string before iteration
 * begins, so the message can only describe the rule — "expecting this class to
 * have tests" — never the missing file. Blueprint::targeted() invokes predicate,
 * then line finder, then failure, in that order for each object, so a message
 * recorded by the predicate is readable by the failure callback that follows it.
 *
 * The violation always lands on the class declaration. The fault this package
 * reports is a file that does not exist somewhere else, so there is no offending
 * line inside the reported file to point at.
 */
final class Rule
{
    /**
     * @param  Expectation<array<int, string>|string>  $expectation  Exactly the type
     *                                                               Targets::fromExpectation
     *                                                               and SingleArchExpectation
     *                                                               take. Expectation's
     *                                                               template is invariant,
     *                                                               so nothing wider passes.
     * @param  callable(ObjectDescription): ?string  $inspect  Returns null when the
     *                                                         object satisfies the
     *                                                         rule, or the failure
     *                                                         message when it does not.
     */
    public static function make(Expectation $expectation, callable $inspect): ArchExpectation
    {
        /**
         * Never observed. Blueprint::targeted() calls the failure callback only
         * after the predicate returned false in the same iteration, and the
         * predicate returns false only when it has just recorded a message. The
         * initial value exists so the variable is a string rather than a
         * ?string, which is what lets the failure callback use it directly.
         */
        $message = '';

        $blueprint = Blueprint::make(
            Targets::fromExpectation($expectation),
            Dependencies::fromExpectationInput([]),
        );

        return SingleArchExpectation::fromExpectation(
            $expectation,
            function (LayerOptions $options) use ($blueprint, $inspect, &$message): void {
                $blueprint->targeted(
                    function (ObjectDescription $object) use ($inspect, &$message): bool {
                        $found = $inspect($object);
                        $message = $found ?? '';

                        return $found === null;
                    },
                    $options,
                    /**
                     * Captured by reference explicitly. An arrow function would
                     * bind $message by value at the moment Blueprint::targeted()
                     * is called — before the predicate has run — and every
                     * message would be empty.
                     */
                    function (Violation $violation) use (&$message): never {
                        throw new ArchExpectationFailedException($violation, $message);
                    },
                    FileLineFinder::where(
                        fn (string $candidate): bool => str_contains($candidate, 'class'),
                    ),
                );
            },
        );
    }
}
