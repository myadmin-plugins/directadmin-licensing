---
name: phpunit-tests
description: Writes PHPUnit test methods matching patterns in `tests/DirectadminIncTest.php` and `tests/PluginTest.php`. Use when user says 'write tests', 'add test for', 'test this function', or adds new functions to `src/`. Covers `assertStringContainsString()` source-scan tests, `ReflectionClass` method signature tests, and return value assertions. Do NOT use for integration tests requiring a live DA API or database.
---
# phpunit-tests

## Critical

- **Never call functions that hit external services** (cURL, DB, MyAdmin globals). Test only pure functions or use static source-scan assertions.
- **Do not mock the DA API.** These tests are intentionally offline. If live API behavior must be tested, that is outside this skill's scope.
- All test classes go in `tests/`, namespace `Detain\MyAdminDirectadmin\Tests`, extend `PHPUnit\Framework\TestCase`.
- Run with the PHPUnit binary (`vendor/bin/phpunit`) from the package root. Verify green before committing.

## Instructions

### 1. Choose the right test class

| What you're testing | File to add to |
|---|---|
| Functions in `src/directadmin.inc.php` | `tests/DirectadminIncTest.php` |
| `Plugin` class structure / hooks | `tests/PluginTest.php` |
| File/package structure | `tests/FileExistenceTest.php` |

Verify the target file exists before editing: `ls tests/`

### 2. Source-scan test (function existence)

Use this pattern to assert a function is defined in `src/directadmin.inc.php` without calling it:

```php
/**
 * Tests that my_new_function() is defined in the source.
 * One-line description of what it does.
 */
public function testMyNewFunctionIsDefined(): void
{
    $this->assertStringContainsString(
        'function my_new_function(',
        self::$sourceContents
    );
}
```

`self::$sourceContents` is loaded in `setUpBeforeClass()` from `dirname(__DIR__) . '/src/directadmin.inc.php'`. No additional setup needed.

Verify: method name follows `test` + PascalCase + `IsDefined`.

### 3. Parameter signature test

When the exact signature matters (order/defaults), use `assertMatchesRegularExpression()`:

```php
public function testMyNewFunctionParameterSignature(): void
{
    $this->assertMatchesRegularExpression(
        '/function my_new_function\(\$ipAddress,\s*\$ostype,\s*\$pass\s*=\s*\'\'\)/',
        self::$sourceContents
    );
}
```

Verify the regex matches the actual signature in `src/directadmin.inc.php` before committing.

### 4. Pure-function return value test

Only call a function directly if it has **no side effects and no external dependencies**. Load the source first:

```php
public function testMyPureFunctionReturnsExpectedValue(): void
{
    require_once self::$sourceFile; // idempotent — safe to repeat

    $result = my_pure_function();

    $this->assertIsArray($result);
    $this->assertNotEmpty($result);
    $this->assertArrayHasKey('expected_key', $result);
}
```

The only function currently safe to call directly is `get_directadmin_license_types()`.

### 5. ReflectionClass method signature test (Plugin)

Use `$this->reflection` (set up in `PluginTest::setUp()`) to inspect `Plugin` methods without instantiating the full MyAdmin stack:

```php
public function testGetNewHandlerMethodSignature(): void
{
    $method = $this->reflection->getMethod('getNewHandler');
    $this->assertTrue($method->isPublic());
    $this->assertTrue($method->isStatic());

    $params = $method->getParameters();
    $this->assertCount(1, $params);
    $this->assertSame('event', $params[0]->getName());

    $type = $params[0]->getType();
    $this->assertNotNull($type);
    $this->assertSame(GenericEvent::class, $type->getName());
}
```

Verify `use Symfony\Component\EventDispatcher\GenericEvent;` is already imported at the top of `tests/PluginTest.php`.

### 6. Hook registration test (Plugin)

When adding a new hook to `Plugin::getHooks()`, add two tests:

```php
// 1. Key exists
public function testGetHooksContainsNewEvent(): void
{
    $hooks = Plugin::getHooks();
    $this->assertArrayHasKey('licenses.new_event', $hooks);
}

// 2. Count guard — update the expected count
public function testGetHooksCount(): void
{
    $hooks = Plugin::getHooks();
    $this->assertCount(7, $hooks); // was 6, now 7
}
```

Verify the count in `testGetHooksCount()` matches the actual number of entries returned by `Plugin::getHooks()`.

### 7. Update function count guard

When adding a function to `src/directadmin.inc.php`, update `testFunctionCount()` in `tests/DirectadminIncTest.php`:

```php
public function testFunctionCount(): void
{
    preg_match_all('/^\s*function\s+\w+\s*\(/m', self::$sourceContents, $matches);
    $this->assertCount(17, $matches[0], 'Expected 17 function definitions in directadmin.inc.php');
    //                  ^^ increment from 16
}
```

Run `phpunit --filter testFunctionCount` (using the binary in `vendor/bin/`) to confirm the new count is correct.

## Examples

**User says:** "I added `directadmin_get_usage($lid)` to `src/directadmin.inc.php`. Write tests for it."

**Actions taken:**
1. Add to `tests/DirectadminIncTest.php`:
```php
public function testDirectadminGetUsageIsDefined(): void
{
    $this->assertStringContainsString(
        'function directadmin_get_usage(',
        self::$sourceContents
    );
}

public function testDirectadminGetUsageParameterSignature(): void
{
    $this->assertMatchesRegularExpression(
        '/function directadmin_get_usage\(\$lid\)/',
        self::$sourceContents
    );
}
```
2. Update `testFunctionCount()` in `tests/DirectadminIncTest.php`: `assertCount(17, ...)` (was 16).
3. Run the PHPUnit test suite — all green.

**Result:** Two new passing tests plus an updated count guard.

## Common Issues

**`Class 'Detain\MyAdminDirectadmin\Tests\...' not found`**
1. Check the namespace at the top of your test file is exactly `Detain\MyAdminDirectadmin\Tests`.
2. Run `composer dump-autoload` — the `Tests` namespace is declared under `autoload-dev` in `composer.json`.

**`assertCount(16, ...) failed, actual count 17`** in `testFunctionCount`
- You added a function but forgot to update the count. Change `assertCount(16,` to `assertCount(17,`.

**`method_exists(): Argument #1 must be of type object|string`**
- You referenced a method name that doesn't exist on `Plugin`. Verify the method name with `$this->reflection->getMethods()`.

**`ReflectionException: Method getXxx does not exist`**
- The method hasn't been added to `src/Plugin.php` yet, or its name differs. Check `src/Plugin.php` for the exact method name.

**`require_once` causes fatal error / undefined constant**
- You called a function that depends on MyAdmin globals (`$GLOBALS['tf']`, constants like `DIRECTADMIN_USERNAME`). Only call `get_directadmin_license_types()` directly; use source-scan assertions for everything else.

**Test passes locally but fails in CI**
- CI runs the test suite using config from `phpunit.xml.dist` (see `.github/workflows/tests.yml`). Ensure your test class is inside `tests/` and the filename matches the class name exactly (PHP is case-sensitive on Linux).
