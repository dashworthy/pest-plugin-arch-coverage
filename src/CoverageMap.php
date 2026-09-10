<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchCoverage;

use InvalidArgumentException;

/**
 * The mirror between a source directory tree and its test directory tree,
 * walked forwards: given a source file, where would its test live?
 *
 * Directories rather than namespaces. A Pest test file declares no class, so
 * class_exists() is false for every test in a Pest suite and there is nothing to
 * look up by name; whether a test exists is a question about a file.
 *
 * Path arithmetic only. Whether a candidate is actually there is is_file()'s
 * question, asked by the caller, which is what makes this testable against paths
 * that never touch disk.
 */
final readonly class CoverageMap
{
    /**
     * @param  array<string, array<int, string>>  $map  Normalised: every root
     *                                                  separator-free at the end,
     *                                                  every value a list.
     */
    private function __construct(
        private array $map,
        private string $suffix,
    ) {}

    /**
     * @param  array<string, string|array<int, string>>  $map  Source directory =>
     *                                                         one or more test
     *                                                         directories. Any one
     *                                                         candidate satisfies
     *                                                         the file.
     */
    public static function make(array $map, string $suffix = 'Test'): self
    {
        if ($map === []) {
            throw new InvalidArgumentException(
                'The coverage map is empty, so no class could be checked. Pass a source '.
                'directory mapped to one or more test directories, e.g. '.
                "toHaveTests([app_path('Domains') => base_path('tests/Unit/Domains')]).",
            );
        }

        $normalised = [];

        foreach ($map as $source => $tests) {
            $normalised[self::normalise($source)] = array_map(
                self::normalise(...),
                is_array($tests) ? array_values($tests) : [$tests],
            );
        }

        return new self($normalised, $suffix);
    }

    /**
     * Every path at which a test for the given source file would satisfy the
     * rule. An empty list means the file is under no mapped source root.
     *
     * @return array<int, string>
     */
    public function testCandidatesFor(string $sourceFile): array
    {
        $file = self::normalise($sourceFile);
        $source = $this->longestRoot($file);

        if ($source === null) {
            return [];
        }

        if (! str_ends_with($file, '.php')) {
            return [];
        }

        $relative = substr($file, strlen($source) + 1, -4).$this->suffix.'.php';

        return array_map(
            static fn (string $test): string => $test.'/'.$relative,
            $this->map[$source],
        );
    }

    /**
     * The most specific source root containing the file, so a subtree can point
     * at a different test root without restating its parent.
     */
    private function longestRoot(string $file): ?string
    {
        $matched = null;

        foreach (array_keys($this->map) as $root) {
            /**
             * The separator is part of the comparison, or '/src/app' would claim
             * every file under '/src/application' and rewrite it into a candidate
             * path that means nothing.
             */
            if (! str_starts_with($file, $root.'/')) {
                continue;
            }

            if ($matched === null || strlen($root) > strlen($matched)) {
                $matched = $root;
            }
        }

        return $matched;
    }

    /**
     * realpath() where the path exists, so a root reached through a symlink
     * compares equal to the same directory named literally. Paths that do not
     * exist are left alone: a candidate test file is not supposed to be there yet.
     */
    private static function normalise(string $path): string
    {
        return rtrim(realpath($path) ?: $path, '/');
    }
}
