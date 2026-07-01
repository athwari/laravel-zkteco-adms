<?php

use Athwari\LaravelZktecoAdms\Models\ZktecoAttendanceLog;
use Athwari\LaravelZktecoAdms\Models\ZktecoDevice;
use Athwari\LaravelZktecoAdms\Models\ZktecoDeviceCommand;
use Athwari\LaravelZktecoAdms\Models\ZktecoDeviceEvent;
use Athwari\LaravelZktecoAdms\Models\ZktecoUser;

return [
    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | All ZKTeco ADMS tables will be prefixed with this value.
    |
    */
    'table_prefix' => env('ZKTECO_TABLE_PREFIX', 'zkteco_'),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Configure the ADMS protocol HTTP endpoints.
    |
    */
    'routes' => [
        'prefix' => env('ZKTECO_ROUTE_PREFIX', 'iclock'),
        'middleware' => [],
        'domain' => env('ZKTECO_ROUTE_DOMAIN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Settings
    |--------------------------------------------------------------------------
    */
    'device' => [
        'auto_register' => env('ZKTECO_AUTO_REGISTER_DEVICES', true),
        'offline_threshold_minutes' => env('ZKTECO_OFFLINE_THRESHOLD', 10),
        'max_devices' => env('ZKTECO_MAX_DEVICES', 1000),
        'default_trans_interval' => 1,
        'default_trans_times' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Settings
    |--------------------------------------------------------------------------
    |
    | Default response parameters for ADMS protocol handshake.
    |
    */
    'response' => [
        'stamp' => 9999999999,
        'error_delay' => 60,
        'delay' => 30,
        'realtime' => 1,
        'encrypt' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    */
    'max_body_size' => env('ZKTECO_MAX_BODY_SIZE', 10 * 1024 * 1024),
    'max_commands_per_device' => env('ZKTECO_MAX_COMMANDS_PER_DEVICE', 100),

    /*
    |--------------------------------------------------------------------------
    | Device Eviction
    |--------------------------------------------------------------------------
    |
    | Automatically mark stale devices as offline.
    |
    */
    'device_eviction_enabled' => env('ZKTECO_DEVICE_EVICTION_ENABLED', true),
    'device_eviction_interval' => env('ZKTECO_DEVICE_EVICTION_INTERVAL', 300),
    'device_eviction_timeout' => env('ZKTECO_DEVICE_EVICTION_TIMEOUT', 86400),

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    */
    'default_timezone' => env('ZKTECO_DEFAULT_TIMEZONE', 'UTC'),
    'storage_timezone' => env('ZKTECO_ADMS_STORAGE_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Inspect Endpoint
    |--------------------------------------------------------------------------
    |
    | Enable the /inspect endpoint for debugging device state.
    |
    */
    'enable_inspect' => env('ZKTECO_ENABLE_INSPECT', false),

    /*
    |--------------------------------------------------------------------------
    | Configurable Models
    |--------------------------------------------------------------------------
    |
    | Override these to extend the default models with custom logic.
    |
    */
    'models' => [
        'device' => ZktecoDevice::class,
        'attendance_log' => ZktecoAttendanceLog::class,
        'user' => ZktecoUser::class,
        'device_command' => ZktecoDeviceCommand::class,
        'device_event' => ZktecoDeviceEvent::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | App User Model
    |--------------------------------------------------------------------------
    |
    | The host application's User model, used for the optional app_user_id
    | foreign key on ZktecoUser.
    |
    */
    'user_model' => 'App\Models\User',

    /*
    |--------------------------------------------------------------------------
    | Event Dispatching
    |--------------------------------------------------------------------------
    |
    | Toggle individual event dispatching on or off.
    |
    */
    'events' => [
        'dispatch_attendance_received' => true,
        'dispatch_device_registered' => true,
        'dispatch_device_connected' => true,
        'dispatch_command_result' => true,
        'dispatch_user_synced' => true,
        'dispatch_device_event' => true,
    ],
];
