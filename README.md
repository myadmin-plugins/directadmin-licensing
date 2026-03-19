# DirectAdmin Licensing Plugin for MyAdmin

[![Tests](https://github.com/detain/myadmin-directadmin-licensing/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-directadmin-licensing/actions/workflows/tests.yml)
[![Codecov](https://codecov.io/gh/detain/myadmin-directadmin-licensing/branch/master/graph/badge.svg)](https://codecov.io/gh/detain/myadmin-directadmin-licensing)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-directadmin-licensing/version)](https://packagist.org/packages/detain/myadmin-directadmin-licensing)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-directadmin-licensing/downloads)](https://packagist.org/packages/detain/myadmin-directadmin-licensing)
[![License](https://poser.pugx.org/detain/myadmin-directadmin-licensing/license)](https://packagist.org/packages/detain/myadmin-directadmin-licensing)

A MyAdmin plugin that integrates DirectAdmin license management into the billing and provisioning system. It supports selling, activating, deactivating, and managing both paid and free-tier DirectAdmin server and VPS licenses via the DirectAdmin API.

## Features

- Automated license provisioning when customers purchase DirectAdmin licenses
- License activation and deactivation through the DirectAdmin API
- IP address change support for migrating licenses between servers
- Free-tier license management for qualifying VPS packages
- OS type detection and mapping for license compatibility
- Admin panel integration with license management menus and settings
- Payment processing for newly created licenses

## Installation

Install with Composer:

```sh
composer require detain/myadmin-directadmin-licensing
```

## Usage

This package is designed to run within the MyAdmin hosting management platform. The `Plugin` class registers event hooks that the MyAdmin framework dispatches during license lifecycle operations.

### Plugin Registration

The plugin is auto-discovered by MyAdmin through Composer's plugin installer. It registers handlers for:

- `licenses.settings` -- Admin configuration fields (API credentials, stock settings)
- `licenses.activate` / `licenses.reactivate` -- License provisioning on purchase
- `licenses.deactivate` / `licenses.deactivate_ip` -- License cancellation
- `function.requirements` -- Lazy-loading of procedural API functions

### Available Functions

| Function | Description |
|---|---|
| `get_directadmin_license_types()` | Returns supported OS type mappings |
| `activate_directadmin()` | Creates and activates a paid license |
| `deactivate_directadmin()` | Cancels an active license |
| `get_directadmin_licenses()` | Lists all licenses on the account |
| `get_directadmin_license_by_ip()` | Finds a license by IP address |
| `directadmin_modify_os()` | Changes the OS type of a license |
| `activate_free_license()` | Provisions a free-tier license |
| `delete_free_license()` | Removes a free-tier license |

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

To generate a coverage report:

```sh
vendor/bin/phpunit --coverage-html build/coverage
```

## License

This package is licensed under the [LGPL-2.1](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html) license.
