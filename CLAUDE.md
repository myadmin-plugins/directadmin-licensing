# DirectAdmin Licensing Plugin

MyAdmin DirectAdmin licensing plugin for DirectAdmin license provisioning, activation, deactivation, and OS management via the DirectAdmin API.

## Commands

```bash
composer install                          # install deps
vendor/bin/phpunit                        # run all tests
vendor/bin/phpunit --coverage-html build/coverage  # coverage report
php bin/directadmin_licenses.php          # list all DA licenses
php bin/directadmin_get_os_list.php       # fetch OS list from DA API
php bin/directadmin_get_products.php      # fetch product list from DA API
```

```bash
composer dump-autoload                    # refresh PSR-4 classmap after adding classes
vendor/bin/phpunit --filter testFunctionCount   # verify function count guard
vendor/bin/phpunit --filter PluginTest    # verify plugin structure only
```

## Architecture

**Entry:** `src/Plugin.php` · **API functions:** `src/directadmin.inc.php` · **CLI scripts:** `bin/`

**CI/CD:** `.github/` contains CI/CD workflows (`.github/workflows/tests.yml`) for automated test runs on push and pull request.

**IDE:** `.idea/` stores PhpStorm project configuration including `inspectionProfiles/Project_Default.xml` for code inspection rules, `deployment.xml` for server mappings, and `encodings.xml` for file encoding settings.

**Plugin class** (`Detain\MyAdminDirectadmin\Plugin`):
- `getHooks()` — registers all event handlers with MyAdmin framework
- `getRequirements()` — lazy-loads `src/directadmin.inc.php` functions via `$loader->add_requirement()` / `add_page_requirement()`
- `getSettings()` — registers admin settings: `DIRECTADMIN_USERNAME`, `DIRECTADMIN_PASSWORD`, `OUTOFSTOCK_LICENSES_DIRECTADMIN`, `DIRECTADMIN_FREE_USERNAME`, `DIRECTADMIN_FREE_PASSWORD`
- `getActivate()` / `getDeactivate()` — license lifecycle, dispatched on `licenses.activate`, `licenses.reactivate`, `licenses.deactivate`, `licenses.deactivate_ip`
- `getChangeIp()` — IP change handler; calls `\Directadmin::editIp()`, records history via `\MyAdmin\App::history()`, sets `$event['status']`
- `getMenu()` — adds admin panel menu links for reusable licenses, breakdown, and list views

**Registered hooks:**
```
licenses.settings      → getSettings()
licenses.activate      → getActivate()
licenses.reactivate    → getActivate()
licenses.deactivate    → getDeactivate()
licenses.deactivate_ip → getDeactivate()
function.requirements  → getRequirements()
```

**Key functions in `src/directadmin.inc.php`:**
- `directadmin_req($page, $post, $options)` — cURL wrapper; auto-builds URLs under `https://www.directadmin.com/`; authenticates via `CURLOPT_USERPWD` Basic auth with `DIRECTADMIN_USERNAME`/`DIRECTADMIN_PASSWORD`
- `activate_directadmin($ipAddress, $ostype, $pass, $email, $name, $domain, $custid)` — calls `createlicense.php` then `directadmin_makepayment()`
- `deactivate_directadmin($ipAddress)` / `directadmin_deactivate($ipAddress)` — calls `cgi-bin/deletelicense`; sends admin email on failure via `TFSmarty` + `\MyAdmin\Mail`
- `get_directadmin_licenses()` / `get_directadmin_license_by_ip($ip)` — list and lookup by IP
- `get_directadmin_license($lid)` — fetches a single license record by license ID
- `directadmin_ip_to_lid($ipAddress)` — maps an IP address to its license ID
- `directadmin_makepayment($lid)` — processes payment for a newly created license via `cgi-bin/makepayment`
- `directadmin_get_os_list($active)` — fetches available OS list from DA API
- `directadmin_get_products()` — fetches product catalog from DA API
- `activate_free_license()` / `delete_free_license()` — delegates to `FreeDirectAdmin` class via `function_requirements()`
- `directadmin_modify_os($lid, $os)` — validates against live OS list before calling `special.php`
- `get_directadmin_license_types()` — static map of DA OS keys → human labels
- `directadmin_get_best_type($module, $packageId)` — resolves VPS template OS to DA type string

**Bin scripts** bootstrap via `require_once __DIR__.'/../../../../include/functions.inc.php'` (MyAdmin root).

**StatisticClient** — `directadmin_req()` optionally ticks/reports to `workerman/statistics` if `StatisticClient.php` is present. Not required.

## Conventions

- Namespace: `Detain\MyAdminDirectadmin` → `src/` · Tests: `Detain\MyAdminDirectadmin\Tests` → `tests/`
- All `Plugin` methods are `public static` and accept `GenericEvent $event`
- Check `$event['category'] == get_service_define('DIRECTADMIN')` before acting; call `$event->stopPropagation()` after
- Log with `myadmin_log(self::$module, 'info'|'error', $message, __LINE__, __FILE__, self::$module, $id)`
- Paid vs free license branching: check `run_event('get_free_da_service_types', true, 'licenses')` then dispatch to correct activate/delete function
- After activate: `$serviceClass->setKey($response)->save()`
- New requirements: register in `getRequirements()` with `$loader->add_requirement()` (always loaded) or `add_page_requirement()` (lazy)
- Coding style: tabs, camelCase params/props, end-of-line braces (see `.scrutinizer.yml`)

## Testing

- Config: `phpunit.xml.dist` · Bootstrap: `vendor/autoload.php`
- Test classes extend `PHPUnit\Framework\TestCase`
- `tests/DirectadminIncTest.php` — tests `src/directadmin.inc.php` function signatures and return values
- `tests/PluginTest.php` — tests `Plugin` static properties, hook registration, method signatures via `ReflectionClass`
- `tests/FileExistenceTest.php` — tests required files and `composer.json` validity
- Pattern: `setUpBeforeClass()` loads source file; individual tests use `assertStringContainsString()` on source content

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `/home/my/.nvm/versions/node/v24.15.0/bin/caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

**Valid `/home/my/.nvm/versions/node/v24.15.0/bin/caliber refresh` options:** `--quiet` (suppress output) and `--dry-run` (preview without writing). Do not pass any other flags — options like `--auto-approve`, `--debug`, or `--force` do not exist and will cause errors.

**`/home/my/.nvm/versions/node/v24.15.0/bin/caliber config`** takes no flags — it runs an interactive provider setup. Do not pass `--provider`, `--api-key`, or `--endpoint`.

If `/home/my/.nvm/versions/node/v24.15.0/bin/caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:model-config -->
## Model Configuration

Recommended default: `claude-sonnet-4-6` with high effort (stronger reasoning; higher cost and latency than smaller models).
Smaller/faster models trade quality for speed and cost — pick what fits the task.
Pin your choice (`/model` in Claude Code, or `CALIBER_MODEL` when using Caliber with an API provider) so upstream default changes do not silently change behavior.

<!-- /caliber:managed:model-config -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `/home/my/.nvm/versions/node/v24.15.0/bin/caliber refresh`.
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->
