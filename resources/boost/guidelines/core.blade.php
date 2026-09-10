## Test coverage expectations

This project can assert that every class under a source directory has a test file
at the mirrored position, with `toHaveTests()` from
`dashworthy/pest-plugin-arch-coverage`.

- The map is an argument, never a config file:
  `->toHaveTests([app_path('Domains') => base_path('tests/Unit/Domains')])`.
- Any one candidate path satisfies a class. Longest matching source root wins.
- Abstract classes, interfaces, traits and enums are skipped by default. Pass a
  shorter `$skip` list to demand tests for a kind.
- The `$exempt` list names **ancestors**. Naming a class there does not exempt
  that class itself.
- When this expectation fails, it names the exact path to create. Create the test
  at that path. Do NOT add the class to the exempt list to silence it, and do NOT
  add a `@pest-arch-ignore-line` annotation.
