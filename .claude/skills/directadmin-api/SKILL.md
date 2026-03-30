---
name: directadmin-api
description: Adds a new DirectAdmin API function to `src/directadmin.inc.php` using the `directadmin_req()` cURL wrapper. Use when user says 'add API call', 'new DA endpoint', 'call directadmin', or needs a new function in `directadmin.inc.php`. Handles `DIRECTADMIN_USERNAME`/`DIRECTADMIN_PASSWORD` auth and `myadmin_log()` logging automatically via the wrapper. Do NOT use for modifying `Plugin.php` hooks or settings registration.
---
# DirectAdmin API

## Critical

- **Never** build cURL requests manually — all HTTP calls MUST go through `directadmin_req($url, $post, $options)` in `src/directadmin.inc.php`.
- **Never** hardcode credentials — always use the `DIRECTADMIN_USERNAME` and `DIRECTADMIN_PASSWORD` constants in `$post`.
- `StatisticClient` tick/report is handled inside `directadmin_req()` automatically — do NOT add it to your new function.
- After adding a function, you MUST update `tests/DirectadminIncTest.php`: increment the count in `testFunctionCount()` (currently asserts 16) and add a `testYourFunctionIsDefined()` test.
- All API endpoints live under `https://www.directadmin.com/clients/api/` (PHP scripts) or `https://www.directadmin.com/cgi-bin/` (CGI endpoints).

## Instructions

### Step 1 — Identify the endpoint type

Determine the correct base URL:
- Read-only data endpoints: `https://www.directadmin.com/clients/api/{endpoint}.php`
- Mutating CGI actions: `https://www.directadmin.com/cgi-bin/{action}`

Verify the endpoint exists in the DA portal docs or an existing `bin/` script before proceeding.

### Step 2 — Write the function in `src/directadmin.inc.php`

Append the function at the bottom of the file. Use this exact structure:

**For a read-only API endpoint** (mirrors `directadmin_get_os_list`, `directadmin_get_products`):
```php
/**
 * Short description of what this fetches.
 *
 * @param string $paramName Description
 * @return string Raw API response
 */
function directadmin_get_something($paramName = '')
{
    $url = 'https://www.directadmin.com/clients/api/something.php';
    $post = [
        'uid' => DIRECTADMIN_USERNAME,
        'id' => DIRECTADMIN_USERNAME,
        'password' => DIRECTADMIN_PASSWORD,
        'api' => 1,
    ];
    $response = directadmin_req($url, $post);
    myadmin_log('licenses', 'info', $response, __LINE__, __FILE__);
    return $response;
}
```

**For a mutating endpoint** (mirrors `activate_directadmin`, `directadmin_makepayment`):
```php
/**
 * Short description of the action.
 *
 * @param string $lid License ID
 * @param string $param Additional param
 * @return string|false API response or false on failure
 */
function directadmin_do_action($lid, $param)
{
    myadmin_log('licenses', 'info', "Called directadmin_do_action({$lid}, {$param})", __LINE__, __FILE__);
    $url = 'https://www.directadmin.com/cgi-bin/someaction';
    $post = [
        'uid' => DIRECTADMIN_USERNAME,
        'id' => DIRECTADMIN_USERNAME,
        'password' => DIRECTADMIN_PASSWORD,
        'api' => 1,
        'lid' => $lid,
        'param' => $param,
    ];
    $options = [
        CURLOPT_REFERER => 'https://www.directadmin.com/clients/license.php?lid='.$lid
    ];
    $response = directadmin_req($url, $post, $options);
    request_log('licenses', $GLOBALS['tf']->session->account_id, __FUNCTION__, 'directadmin', 'someaction', $post, $response);
    myadmin_log('licenses', 'info', $response, __LINE__, __FILE__);
    return $response;
}
```

Verify: the function appears exactly once in the file and uses `directadmin_req()` — not `getcurlpage()` directly.

### Step 3 — Parse the response if needed

DA API responses are either:
- **Query-string lines** (list endpoints) — parse with `parse_str()`:
  ```php
  $lines = explode("\n", trim($response));
  foreach (array_values($lines) as $line) {
      parse_str($line, $item);
      if (isset($item['lid'])) {
          $results[$item['lid']] = $item;
      }
  }
  return $results;
  ```
- **Key=value lines** (OS list) — parse with `explode('=', $row)`
- **Raw string** (success/error messages) — return directly

Verify: for list endpoints, your return type is `array`; for scalar responses, it is `string|false`.

### Step 4 — Update `tests/DirectadminIncTest.php`

**4a.** Increment the function count assertion (this step uses output from Step 2 — you must know your new total):
```php
// Change:
$this->assertCount(16, $matches[0], 'Expected 16 function definitions in directadmin.inc.php');
// To (if you added 1 function):
$this->assertCount(17, $matches[0], 'Expected 17 function definitions in directadmin.inc.php');
```

**4b.** Add a test for your function's existence in the `DirectadminIncTest` class:
```php
/**
 * Tests that directadmin_get_something() is defined in the source.
 * Brief description of what it does.
 */
public function testDirectadminGetSomethingIsDefined(): void
{
    $this->assertStringContainsString(
        'function directadmin_get_something(',
        self::$sourceContents
    );
}
```

Verify: the PHPUnit test suite passes with no failures.

### Step 5 — Run the tests

Run the full test suite from the package root to confirm all tests pass:

```php
// In tests/DirectadminIncTest.php — confirm updated count guard:
$this->assertCount(17, $matches[0], 'Expected 17 function definitions in src/directadmin.inc.php');
```

Execute `phpunit` (via the binary in `vendor/bin/` or `composer test`) and verify all assertions are green before considering the function complete.

## Examples

**User says:** "Add an API call to suspend a DirectAdmin license by lid"

**Actions taken:**
1. Identify endpoint: mutating action → `https://www.directadmin.com/cgi-bin/suspendlicense`
2. Append to `src/directadmin.inc.php`:
```php
/**
 * Suspend an active DirectAdmin license.
 *
 * @param string $lid License ID to suspend
 * @return string API response
 */
function directadmin_suspend_license($lid)
{
    myadmin_log('licenses', 'info', "Called directadmin_suspend_license({$lid})", __LINE__, __FILE__);
    $url = 'https://www.directadmin.com/cgi-bin/suspendlicense';
    $post = [
        'uid' => DIRECTADMIN_USERNAME,
        'id' => DIRECTADMIN_USERNAME,
        'password' => DIRECTADMIN_PASSWORD,
        'api' => 1,
        'lid' => $lid,
    ];
    $options = [
        CURLOPT_REFERER => 'https://www.directadmin.com/clients/license.php?lid='.$lid
    ];
    $response = directadmin_req($url, $post, $options);
    request_log('licenses', $GLOBALS['tf']->session->account_id, __FUNCTION__, 'directadmin', 'suspendlicense', $post, $response);
    myadmin_log('licenses', 'info', $response, __LINE__, __FILE__);
    return $response;
}
```
3. Update `testFunctionCount()` in `tests/DirectadminIncTest.php`: `assertCount(16, ...)` → `assertCount(17, ...)`
4. Add `testDirectadminSuspendLicenseIsDefined()` test
5. Run the PHPUnit test suite → all green

**Result:** New function available to callers; monitored by StatisticClient via `directadmin_req()`.

## Common Issues

**`testFunctionCount` fails with "Expected 16 function definitions, got 17"**
You added a function but forgot to update the count in `tests/DirectadminIncTest.php:248`. Change `assertCount(16, ...)` to `assertCount(17, ...)`.

**`Call to undefined constant DIRECTADMIN_USERNAME`**
This constant is registered by `Plugin::getSettings()` and only exists inside the MyAdmin runtime. In bin scripts, bootstrap MyAdmin first: `require_once __DIR__.'/../../../../include/functions.inc.php';`. Tests cannot call functions that use this constant without mocking the constant.

**`getcurlpage()` returns `false` / StatisticClient reports failure**
The DA API returned a cURL error. Check: (1) the full URL is correct including scheme; (2) `DIRECTADMIN_USERNAME`/`DIRECTADMIN_PASSWORD` are set; (3) the endpoint path matches DA portal docs exactly — `clients/api/` vs `cgi-bin/` matters.

**Response is empty string instead of data**
DA returns empty body for auth failures, not an HTTP error code. Confirm credentials are correct by testing via `php bin/directadmin_licenses.php` which uses the same auth path.

**`request_log()` — undefined function**
Only call `request_log()` for mutating operations inside the MyAdmin runtime where `$GLOBALS['tf']->session->account_id` is set. Do not call it in read-only functions or it will fatal in CLI contexts.
