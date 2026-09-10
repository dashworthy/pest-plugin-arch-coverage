<?php

use Dashworthy\PestPluginArchCoverage\CoverageMap;

it('mirrors a source file into its test directory', function () {
    $map = CoverageMap::make(['/app' => '/tests/Unit']);

    expect($map->testCandidatesFor('/app/Actions/CreateUser.php'))
        ->toBe(['/tests/Unit/Actions/CreateUserTest.php']);
});

it('offers every test root a source root maps to', function () {
    $map = CoverageMap::make(['/app' => ['/tests/Feature', '/tests/Unit']]);

    expect($map->testCandidatesFor('/app/Actions/CreateUser.php'))->toBe([
        '/tests/Feature/Actions/CreateUserTest.php',
        '/tests/Unit/Actions/CreateUserTest.php',
    ]);
});

it('applies the configured suffix', function () {
    $map = CoverageMap::make(['/app' => '/tests/Unit'], 'Spec');

    expect($map->testCandidatesFor('/app/CreateUser.php'))
        ->toBe(['/tests/Unit/CreateUserSpec.php']);
});

it('prefers the longest matching source root', function () {
    $map = CoverageMap::make([
        '/app' => '/tests/Unit',
        '/app/Http/Controllers' => '/tests/Feature',
    ]);

    expect($map->testCandidatesFor('/app/Http/Controllers/UserController.php'))
        ->toBe(['/tests/Feature/UserControllerTest.php']);
});

it('does not let one root claim a sibling with a shared prefix', function () {
    $map = CoverageMap::make(['/src/app' => '/tests/Unit']);

    expect($map->testCandidatesFor('/src/application/Thing.php'))->toBe([]);
});

it('returns no candidates for a file under no mapped source root', function () {
    $map = CoverageMap::make(['/app' => '/tests/Unit']);

    expect($map->testCandidatesFor('/other/Thing.php'))->toBe([]);
});

it('returns no candidates for a file that is not php', function () {
    $map = CoverageMap::make(['/app' => '/tests/Unit']);

    expect($map->testCandidatesFor('/app/schema.sql'))->toBe([]);
});

it('tolerates a trailing separator on a root', function () {
    $map = CoverageMap::make(['/app/' => '/tests/Unit/']);

    expect($map->testCandidatesFor('/app/CreateUser.php'))
        ->toBe(['/tests/Unit/CreateUserTest.php']);
});

it('resolves a root reached through a symlink to the same map entry', function () {
    // The packages in this repository are symlinked into vendor/, so the path
    // arch reports and the path the caller writes are two spellings of one
    // directory. They must land in the same entry or nothing is ever mapped.
    $real = sys_get_temp_dir().'/arch-coverage-real-'.uniqid();
    $link = sys_get_temp_dir().'/arch-coverage-link-'.uniqid();

    mkdir($real.'/App', recursive: true);
    symlink($real, $link);
    touch($real.'/App/CreateUser.php');

    // The caller names the root through the link; arch reports the source file
    // by its real path. Both are normalised, so they land in the same entry.
    $map = CoverageMap::make([$link.'/App' => '/tests/Unit']);

    expect($map->testCandidatesFor($real.'/App/CreateUser.php'))
        ->toBe(['/tests/Unit/CreateUserTest.php']);

    unlink($link);
    unlink($real.'/App/CreateUser.php');
    rmdir($real.'/App');
    rmdir($real);
});

it('refuses an empty map', function () {
    expect(fn () => CoverageMap::make([]))
        ->toThrow(InvalidArgumentException::class, 'coverage map is empty');
});
