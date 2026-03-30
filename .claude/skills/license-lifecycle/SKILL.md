---
name: license-lifecycle
description: Implements a complete paid or free DirectAdmin license lifecycle operation (activate/deactivate/change-IP) across Plugin.php and directadmin.inc.php. Use when user says 'add license type', 'support new lifecycle event', 'handle IP change', 'extend free/paid branching logic', or modifies getActivate()/getDeactivate(). Covers run_event('get_free_da_service_types') check, function_requirements() loading, and $serviceClass->setKey()->save() pattern. Do NOT use for settings changes, OS modification, or non-DirectAdmin license plugins.
---
# license-lifecycle

## Critical

- **Always guard with `$event['category'] == get_service_define('DIRECTADMIN')`** before any action — without this guard, the handler fires for every license type.
- **Always call `$event->stopPropagation()`** at the end of both `getActivate()` and `getDeactivate()` — omitting it causes double-processing by other handlers.
- **Free vs paid branching is mandatory** — check `run_event('get_free_da_service_types', true, 'licenses')` and call `in_array($serviceClass->getType(), array_keys($freeDaTypes))` before choosing the activation path. Never hard-code the type check.
- **Never call lifecycle functions directly** — always load them first with `function_requirements('function_name')` before invoking `activate_directadmin()`, `deactivate_directadmin()`, `activate_free_license()`, or `delete_free_license()`.
- **`$serviceClass->setKey($response)->save()`** — activation must persist the returned license ID. Deactivation sets `$event['success']` instead.
- All API calls go through `directadmin_req()` — never call `getcurlpage()` directly for DA requests.
- Always log with `myadmin_log(self::$module, 'info', '...', __LINE__, __FILE__, self::$module, $serviceClass->getId())`.

## Instructions

### Step 1 — Register the new hook in `getHooks()` (`src/Plugin.php`)

Add the event name → handler mapping in the returned array:

```php
public static function getHooks()
{
    return [
        self::$module.'.settings'      => [__CLASS__, 'getSettings'],
        self::$module.'.activate'      => [__CLASS__, 'getActivate'],
        self::$module.'.reactivate'    => [__CLASS__, 'getActivate'],  // reactivate reuses getActivate
        self::$module.'.deactivate'    => [__CLASS__, 'getDeactivate'],
        self::$module.'.deactivate_ip' => [__CLASS__, 'getDeactivate'], // deactivate_ip reuses getDeactivate
        'function.requirements'        => [__CLASS__, 'getRequirements']
    ];
}
```

Verify the event name exists in `include/config/hooks.json` (in the MyAdmin root) before proceeding.

### Step 2 — Register any new `inc.php` functions in `getRequirements()` (`src/Plugin.php`)

Use `add_requirement()` for functions needed on every page load, `add_page_requirement()` for lazy/on-demand functions:

```php
public static function getRequirements(GenericEvent $event)
{
    $loader = $event->getSubject(); // \MyAdmin\Plugins\Loader
    // always-loaded:
    $loader->add_requirement('get_directadmin_licenses',
        '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
    // on-demand (called via function_requirements() before use):
    $loader->add_page_requirement('activate_directadmin',
        '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
    $loader->add_page_requirement('activate_free_license',
        '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
    $loader->add_page_requirement('deactivate_directadmin',
        '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
    $loader->add_page_requirement('delete_free_license',
        '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
}
```

Verify each function name exactly matches its `function` declaration in `src/directadmin.inc.php`.

### Step 3 — Implement `getActivate()` with free/paid branching (`src/Plugin.php`)

```php
public static function getActivate(GenericEvent $event)
{
    $serviceClass = $event->getSubject();
    if ($event['category'] == get_service_define('DIRECTADMIN')) {
        myadmin_log(self::$module, 'info', 'Directadmin Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $freeDaTypes = run_event('get_free_da_service_types', true, 'licenses');
        if (in_array($serviceClass->getType(), array_keys($freeDaTypes))) {
            // FREE path
            function_requirements('activate_free_license');
            $response = activate_free_license(
                $serviceClass->getIp(),
                $serviceClass->getType(),
                $event['email'],
                $serviceClass->getHostname()
            );
        } else {
            // PAID path
            function_requirements('directadmin_get_best_type');
            function_requirements('activate_directadmin');
            $response = activate_directadmin(
                $serviceClass->getIp(),
                directadmin_get_best_type(self::$module, $serviceClass->getType()),
                $event['email'],
                $event['email'],
                self::$module.$serviceClass->getId(),
                ''
            );
        }
        $serviceClass
            ->setKey($response)
            ->save();
        $event->stopPropagation();
    }
}
```

Verify `$response` is non-false before calling `setKey()` if you need to guard against API failures.

### Step 4 — Implement `getDeactivate()` with free/paid branching (`src/Plugin.php`)

```php
public static function getDeactivate(GenericEvent $event)
{
    $serviceClass = $event->getSubject();
    if ($event['category'] == get_service_define('DIRECTADMIN')) {
        myadmin_log(self::$module, 'info', 'Directadmin Deactivation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $freeDaTypes = run_event('get_free_da_service_types', true, 'licenses');
        if (in_array($serviceClass->getType(), array_keys($freeDaTypes))) {
            // FREE path — uses stored key (lid), not IP
            function_requirements('delete_free_license');
            $response = delete_free_license($serviceClass->getKey(), $serviceClass->getType());
            $event['success'] = true;
        } else {
            // PAID path — uses IP
            function_requirements('deactivate_directadmin');
            $event['success'] = deactivate_directadmin($serviceClass->getIp());
        }
        $event->stopPropagation();
    }
}
```

Note: deactivation does NOT call `setKey()->save()`. It sets `$event['success']`.

### Step 5 — Implement the backing function in `src/directadmin.inc.php`

Follow this exact structure for any new lifecycle function:

```php
/**
 * @param string $ipAddress
 * @return bool|string
 */
function your_new_da_function($ipAddress)
{
    myadmin_log('licenses', 'info', "Called your_new_da_function({$ipAddress})", __LINE__, __FILE__);
    $url = 'https://www.directadmin.com/clients/api/endpoint.php';
    $post = [
        'uid'      => DIRECTADMIN_USERNAME,
        'id'       => DIRECTADMIN_USERNAME,
        'password' => DIRECTADMIN_PASSWORD,
        'api'      => 1,
        'ip'       => $ipAddress,
    ];
    $response = directadmin_req($url, $post);
    myadmin_log('licenses', 'info', $response, __LINE__, __FILE__);
    return $response;
}
```

Verify the function is registered in `getRequirements()` (Step 2) before using `function_requirements()` to call it.

### Step 6 — Run tests

```bash
cd /home/sites/mystage/vendor/detain/myadmin-directadmin-licensing
vendor/bin/phpunit
```

All tests in `tests/PluginTest.php` and `tests/DirectadminIncTest.php` must pass.

## Examples

**User says:** "Add a `licenses.suspend` event that suspends a paid DA license by IP"

**Actions taken:**
1. Add `self::$module.'.suspend' => [__CLASS__, 'getSuspend']` to `getHooks()`.
2. Add `$loader->add_page_requirement('suspend_directadmin', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php')` to `getRequirements()`.
3. Implement `getSuspend()` in `Plugin.php`:
   ```php
   public static function getSuspend(GenericEvent $event)
   {
       $serviceClass = $event->getSubject();
       if ($event['category'] == get_service_define('DIRECTADMIN')) {
           myadmin_log(self::$module, 'info', 'Directadmin Suspension', __LINE__, __FILE__, self::$module, $serviceClass->getId());
           $freeDaTypes = run_event('get_free_da_service_types', true, 'licenses');
           if (!in_array($serviceClass->getType(), array_keys($freeDaTypes))) {
               function_requirements('suspend_directadmin');
               $event['success'] = suspend_directadmin($serviceClass->getIp());
           }
           $event->stopPropagation();
       }
   }
   ```
4. Add `suspend_directadmin($ipAddress)` to `src/directadmin.inc.php` calling `directadmin_req()` with the DA suspend endpoint.

**Result:** `licenses.suspend` events for DA licenses route to `getSuspend()`, free types are skipped, propagation is stopped.

## Common Issues

- **Handler fires for every license type, not just DA:** Missing `if ($event['category'] == get_service_define('DIRECTADMIN'))` guard. Wrap all logic in that condition.

- **`Call to undefined function activate_directadmin()`:** You called the function without `function_requirements('activate_directadmin')` immediately before it, or the function is not registered in `getRequirements()` via `add_page_requirement()`. Add both.

- **`$serviceClass->setKey()` not persisting:** `->save()` was not chained. The pattern must be `$serviceClass->setKey($response)->save();` — both calls are required.

- **Event continues firing after handler:** `$event->stopPropagation()` is missing at the end of the handler. Add it as the last line inside the category guard.

- **Free licenses incorrectly routed to paid path:** `run_event('get_free_da_service_types', true, 'licenses')` returns an associative array — the check must be `in_array($serviceClass->getType(), array_keys($freeDaTypes))`, not `in_array($serviceClass->getType(), $freeDaTypes)`.

- **`deactivate_directadmin()` returns null instead of bool:** The function only returns `true` on successful API call; it returns `null` (implicit) if the license is not active or not found. Check `$event['success'] = deactivate_directadmin(...)` — treat `null` as failure in calling code.

- **Tests fail with "Class 'StatisticClient' not found":** This is expected — `directadmin_req()` checks `is_file()` before requiring `StatisticClient.php`. The test environment simply won't tick stats, which is fine.