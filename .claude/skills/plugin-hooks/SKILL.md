---
name: plugin-hooks
description: Adds a new event hook to src/Plugin.php following the getHooks() + getRequirements() pattern. Use when user says 'add hook', 'register event', 'handle new license event', or adds a handler method to Plugin. Covers getHooks() registration, getRequirements() loader entry, and static method with GenericEvent $event signature. Do NOT use for modifying existing hooks or adding functions to directadmin.inc.php alone.
---
# plugin-hooks

## Critical

- Every handler method **must** be `public static function` — the MyAdmin event dispatcher calls these statically.
- Every handler **must** accept exactly `GenericEvent $event` (from `Symfony\Component\EventDispatcher\GenericEvent`) as its only parameter.
- Every handler **must** call `$event->stopPropagation()` before returning if it acted on the event (category matched). Do NOT stop propagation if the category did not match.
- Every handler **must** guard with `if ($event['category'] == get_service_define('DIRECTADMIN'))` before doing any work — other plugins share the same event bus.
- After adding a new function to `src/directadmin.inc.php`, you **must** register it in `getRequirements()`. Use `add_requirement()` for always-needed functions and `add_page_requirement()` for page/request-scope functions.
- Do NOT register the same function name twice in `getRequirements()`.

## Instructions

### Step 1 — Define the event name and handler method name

Decide:
- **Event key:** follows the pattern `self::$module.'.<action>'` (e.g. `licenses.change_ip`, `licenses.suspend`).
- **Method name:** `get` + PascalCase action (e.g. `getChangeIp`, `getSuspend`).

Verify the event name does not already exist in `getHooks()` before proceeding.

### Step 2 — Register the hook in `getHooks()`

Open `src/Plugin.php`. In the `return [...]` array of `getHooks()`, add one line:

```php
self::$module.'.<action>' => [__CLASS__, 'get<Action>'],
```

Existing pattern for reference (`src/Plugin.php:33-41`):

```php
public static function getHooks()
{
    return [
        self::$module.'.settings'      => [__CLASS__, 'getSettings'],
        self::$module.'.activate'      => [__CLASS__, 'getActivate'],
        self::$module.'.reactivate'    => [__CLASS__, 'getActivate'],   // multiple events can share a handler
        self::$module.'.deactivate'    => [__CLASS__, 'getDeactivate'],
        self::$module.'.deactivate_ip' => [__CLASS__, 'getDeactivate'],
        'function.requirements'        => [__CLASS__, 'getRequirements'],
        // ADD NEW ENTRY HERE
        self::$module.'.<action>'      => [__CLASS__, 'get<Action>'],
    ];
}
```

Verify: the array key is unique and the value is `[__CLASS__, 'methodName']`.

### Step 3 — Add the handler method to `src/Plugin.php`

Insert a new `public static` method after the last existing handler (before `getRequirements`):

```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function get<Action>(GenericEvent $event)
{
    $serviceClass = $event->getSubject();
    if ($event['category'] == get_service_define('DIRECTADMIN')) {
        myadmin_log(self::$module, 'info', '<Action Description>', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        // call function_requirements() for any directadmin.inc.php functions you need
        function_requirements('<required_function>');
        // perform action
        $result = <required_function>(/* args from $serviceClass or $event */);
        // set event response fields if needed, e.g.:
        // $event['status'] = 'ok';
        // $event['status_text'] = 'Done.';
        $event->stopPropagation();
    }
}
```

Verify: method is `public static`, accepts `GenericEvent $event`, calls `myadmin_log()` with `self::$module`, and calls `$event->stopPropagation()` inside the `if` block.

### Step 4 — Register new functions in `getRequirements()` (if needed)

If Step 3 calls a function from `src/directadmin.inc.php` that is not already in `getRequirements()`, add it:

```php
// Always-available (loaded at boot):
$loader->add_requirement('<function_name>', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');

// Page/request scope only:
$loader->add_page_requirement('<function_name>', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
```

Use `add_requirement()` for functions called in event handlers. Use `add_page_requirement()` for functions only needed during a web request (e.g. `directadmin_req`, `activate_free_license`).

Existing block for reference (`src/Plugin.php:129-149`).

Verify: no duplicate function names exist in `getRequirements()`.

### Step 5 — Run tests

Run the full test suite from the package root and verify all existing tests pass before considering the task done:

```php
// Quick verification: confirm new hook appears in getHooks()
$hooks = \Detain\MyAdminDirectadmin\Plugin::getHooks();
assert(isset($hooks['licenses.<action>']), 'Hook not registered');
```

Then run `phpunit` via Composer scripts or the binary in `vendor/bin/` to confirm no regressions.

## Examples

**User says:** "Add a hook for `licenses.change_ip` that calls `change_directadmin_ip($oldIp, $newIp)` from `src/directadmin.inc.php`."

**Actions taken:**

1. In `getHooks()` return array, add:
   ```php
   self::$module.'.change_ip' => [__CLASS__, 'getChangeIp'],
   ```

2. Add method to `src/Plugin.php`:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function getChangeIp(GenericEvent $event)
   {
       $serviceClass = $event->getSubject();
       if ($event['category'] == get_service_define('DIRECTADMIN')) {
           myadmin_log(self::$module, 'info', 'IP Change - (OLD:'.$serviceClass->getIp().') (NEW:'.$event['newip'].')', __LINE__, __FILE__, self::$module, $serviceClass->getId());
           function_requirements('change_directadmin_ip');
           $result = change_directadmin_ip($serviceClass->getIp(), $event['newip']);
           if ($result) {
               $serviceClass->set_ip($event['newip'])->save();
               $event['status'] = 'ok';
               $event['status_text'] = 'The IP Address has been changed.';
           } else {
               $event['status'] = 'error';
               $event['status_text'] = 'IP change failed.';
           }
           $event->stopPropagation();
       }
   }
   ```

3. In `getRequirements()`, add:
   ```php
   $loader->add_requirement('change_directadmin_ip', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
   ```

4. Run the PHPUnit test suite — all tests pass.

**Result:** The `licenses.change_ip` event is now handled by this plugin for DirectAdmin-category services.

## Common Issues

**Propagation not stopped / other plugins also handle the event:**
You forgot `$event->stopPropagation()` inside the `if ($event['category'] == ...)` block. Without it, every plugin registered to the same event will run.

**`Call to undefined function <function_name>()`:**
The function from `src/directadmin.inc.php` is not registered in `getRequirements()`. Add `$loader->add_requirement('<function_name>', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');`.

**Event handler never fires:**
- Check that the event key in `getHooks()` exactly matches what `run_event()` dispatches (e.g. `licenses.change_ip` vs `license.change_ip`).
- Confirm `getHooks()` returns `[__CLASS__, 'get<Action>']` not a string.

**`$event->getSubject()` returns null or wrong type:**
The subject is set by the caller via `new GenericEvent($serviceClass, $extraData)`. Access extra data via `$event['key']`, not `$event->getSubject()['key']`.

**Hook registered but method is not `static`:**
The dispatcher calls handlers as `[ClassName, 'method']` which requires `static`. A non-static method will throw a fatal error. Ensure `public static function get<Action>(GenericEvent $event)`.

**Test class not found after adding method:**
Run `composer dump-autoload` if you added a new class file. For methods added to existing `src/Plugin.php`, no autoload refresh is needed.
