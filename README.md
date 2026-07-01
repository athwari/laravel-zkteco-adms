# Laravel ZKteco ADMS

[![Latest Version on Packagist](https://img.shields.io/packagist/v/athwari/laravel-zkteco-adms.svg?style=flat-square)](https://packagist.org/packages/athwari/laravel-zkteco-adms)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/athwari/laravel-zkteco-adms/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/athwari/laravel-zkteco-adms/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/athwari/laravel-zkteco-adms/fix-php-code-style.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/athwari/laravel-zkteco-adms/actions?query=workflow%3A"Fix+PHP+code+style"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/athwari/laravel-zkteco-adms.svg?style=flat-square)](https://packagist.org/packages/athwari/laravel-zkteco-adms)

This package provides the backend ADMS protocol implementation for ZKTeco biometric devices in Laravel.

It handles device communication, attendance ingestion, user synchronization, and command dispatching. The package is intentionally tenancy-agnostic.

If you need tenant-aware behavior in Filament, use athwari/filament-zkteco-adms, which owns tenancy configuration and tenant column management.

## Features

- Device lifecycle management (registration, activity tracking, stale device eviction command)
- ADMS protocol endpoints for cdata, registry, getrequest, devicecmd, inspect, and test
- Attendance log parsing and persistence
- User synchronization from device operation logs and user query responses
- Command queueing, polling, and confirmation handling
- Configurable model bindings via zkteco-adms.models.*
- Optional event dispatching for device, attendance, command, and user sync flows

## Installation

Install the package via Composer:

```bash
composer require athwari/laravel-zkteco-adms
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="zkteco-adms-config"
```

Publish and run the package migrations:

```bash
php artisan vendor:publish --tag="zkteco-adms-migrations"
php artisan migrate
```

Main configuration areas in config/zkteco-adms.php:

- table_prefix: prefix used for all ZKTeco tables
- routes: endpoint prefix, middleware, and optional domain
- device: registration and limits configuration
- response: handshake defaults
- models: model class overrides for device, user, attendance log, command, and event
- events: per-event dispatch toggles
- enable_inspect: enables the inspect endpoint
- default_timezone: fallback timezone used to interpret device-local attendance times
- storage_timezone: timezone used for normalized `occurred_at` values (defaults to UTC)

Set the normalized attendance storage timezone with:

```dotenv
ZKTECO_ADMS_STORAGE_TIMEZONE=UTC
```

Attendance logs retain the device-local time in `recorded_at` for backward compatibility. The same instant is also stored in `occurred_at`, converted to `storage_timezone`; use this field when comparing or reporting attendance across devices in different timezones.

## Usage

### ADMS Endpoints

By default, routes are mounted under /iclock (configurable via zkteco-adms.routes.prefix):

- GET/POST /iclock/cdata
- GET/POST /iclock/registry
- GET /iclock/getrequest
- POST /iclock/devicecmd
- GET /iclock/inspect (when enabled)
- GET/POST /iclock/test

### Services

Core services are container singletons:

- Athwari\LaravelZktecoAdms\Services\DeviceManager
- Athwari\LaravelZktecoAdms\Services\CommandManager
- Athwari\LaravelZktecoAdms\Services\AttendanceParser
- Athwari\LaravelZktecoAdms\Services\DeviceCommandBuilder

### Models
Default models:

- `Athwari\LaravelZktecoAdms\Models\ZktecoDevice`
- `Athwari\LaravelZktecoAdms\Models\ZktecoUser`
- `Athwari\LaravelZktecoAdms\Models\ZktecoAttendanceLog`
- `Athwari\LaravelZktecoAdms\Models\ZktecoDeviceCommand`
- `Athwari\LaravelZktecoAdms\Models\ZktecoDeviceEvent`

You can override these through config('zkteco-adms.models.*') in your application.

### Facade
The package ships with an auto-discovered facade alias: ZktecoAdms

```php
use ZktecoAdms;

$device = ZktecoAdms::registerDevice('SN123456');

if (ZktecoAdms::deviceExists('SN123456')) {
    ZktecoAdms::setDeviceTimezone('SN123456', 'Asia/Riyadh');
}

$commandId = ZktecoAdms::sendCheckCommand('SN123456');

ZktecoAdms::commandBuilder()->reboot('SN123456');
```

If you prefer explicit imports:

```php
use Athwari\LaravelZktecoAdms\Facades\ZktecoAdms;
```

## Testing

Run the test suite with:

```bash
composer test
```

Additional useful scripts:

```bash
composer test-coverage
composer analyse
composer format
```

## Tenancy Boundary

This package no longer owns multi-tenancy configuration.

- Core package responsibility: protocol and domain logic
- Filament plugin responsibility: tenant ownership, tenant columns, and tenant-scoped UI queries

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

Please review [SECURITY](SECURITY.md) if you discover a vulnerability in this package.

## Credits

- [Athwari](https://github.com/athwari)
- [All Contributors](https://github.com/athwari/laravel-zkteco-adms/graphs/contributors)

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.
