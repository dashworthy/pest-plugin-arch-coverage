<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchCoverage;

use PHPUnit\Architecture\Elements\ObjectDescription;
use PHPUnit\Architecture\Enums\ObjectType;

/**
 * Decides whether one object satisfies the coverage rule, and says why not.
 *
 * A class rather than a closure inside the registration file: src/Autoload.php
 * is a $this-rebound closure at file scope, which static analysis cannot type,
 * so anything living there is unanalysed and untestable on its own. Only the
 * adapter belongs there; this is the part with decisions in it.
 */
final readonly class CoverageInspector
{
    /** Structural kinds that need no test unless the caller says otherwise. */
    public const array DEFAULT_SKIP = ['abstract', 'interface', 'trait', 'enum'];

    /**
     * @param  array<int, string>  $skip  Any of 'abstract', 'interface', 'trait', 'enum'.
     * @param  array<int, string>  $exempt  FQCNs whose descendants and implementors
     *                                      are exempt.
     */
    public function __construct(
        private CoverageMap $coverage,
        private array $skip = self::DEFAULT_SKIP,
        private array $exempt = [],
    ) {}

    /**
     * The failure message, or null when the object satisfies the rule.
     */
    public function __invoke(ObjectDescription $object): ?string
    {
        if ($this->isSkippedKind($object) || $this->descendsFromExempt($object)) {
            return null;
        }

        $candidates = $this->coverage->testCandidatesFor($object->path);

        if ($candidates === []) {
            return 'Expecting a test for this class, but it is not under any mapped source root. '.
                'Map the directory it lives in to a test directory, or exclude it with ->ignoring(...).';
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return null;
            }
        }

        return sprintf(
            'Expecting a test for this class, but it has no corresponding test. Create %s. '.
            'Or, if it is genuinely untestable, name a base class in the exempt list, or exclude '.
            'it with ->ignoring(...).',
            implode(', or ', $candidates),
        );
    }

    /**
     * One arm per structural kind, and the order is load bearing: isAbstract()
     * is true of an interface that declares a method and of a trait that
     * declares an abstract one, so an abstract-first order would answer for both
     * of those from the 'abstract' entry and skip them by proxy whenever the
     * skip list names it without naming their own kind.
     *
     * Each arm answers from the skip list rather than falling through to the
     * next, for the same reason: 'abstract' names the abstract class kind, not
     * everything that cannot be instantiated.
     */
    private function isSkippedKind(ObjectDescription $object): bool
    {
        return match ($object->type) {
            ObjectType::_INTERFACE => in_array('interface', $this->skip, true),
            ObjectType::_TRAIT => in_array('trait', $this->skip, true),
            ObjectType::_ENUM => in_array('enum', $this->skip, true),
            ObjectType::_CLASS => $object->reflectionClass->isAbstract() && in_array('abstract', $this->skip, true),
        };
    }

    /**
     * isSubclassOf() covers parents and implemented interfaces, and is false for
     * the class itself — which is the point. The list names ancestors, so a class
     * must earn its exemption on its own account rather than by being named.
     */
    private function descendsFromExempt(ObjectDescription $object): bool
    {
        return array_any($this->exempt, fn (string $ancestor): bool => $object->reflectionClass->isSubclassOf($ancestor));
    }
}
