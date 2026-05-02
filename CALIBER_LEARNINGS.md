# Caliber Learnings

Accumulated patterns and anti-patterns from development sessions.
Auto-managed by [caliber](https://github.com/caliber-ai-org/ai-setup) — do not edit manually.

- **[gotcha:project]** `tests/DirectadminIncTest.php` has a `testFunctionCount()` that asserts an **exact count** of functions in `src/directadmin.inc.php` (currently 16). Every time you add a function to that file, the test will fail until you update `assertCount(16, ...)` to the new total. Run `vendor/bin/phpunit --filter testFunctionCount` to confirm the count before committing.
- **[gotcha:project]** `tests/PluginTest.php` has a `testGetHooksCount()` that asserts an **exact count** of hooks returned by `Plugin::getHooks()` (currently 6). Adding any entry to `getHooks()` will break this test until you update `assertCount(6, ...)` to the new total.
- **[pattern:project]** PHPUnit tests for `src/directadmin.inc.php` use static source-scan assertions (`assertStringContainsString()` on `file_get_contents()`) rather than calling functions directly, because most functions depend on MyAdmin globals (`$GLOBALS['tf']`, `DIRECTADMIN_USERNAME` constant, DB). Only `get_directadmin_license_types()` is safe to call directly in tests — all other functions must be tested via source analysis.
- **[pattern:project]** When adding a new function to `src/directadmin.inc.php`, three test updates are always required: (1) add a `testYourFunctionIsDefined()` source-scan test to `tests/DirectadminIncTest.php`, (2) update `testFunctionCount()` count, (3) optionally add a `testYourFunctionParameterSignature()` regex test if the signature is load-bearing.
- **[pattern:project]** When adding a new hook to `Plugin::getHooks()`, two test updates are always required in `tests/PluginTest.php`: (1) add `assertArrayHasKey('licenses.new_event', $hooks)`, (2) update `testGetHooksCount()` to the new count.
