<?php

use Athwari\LaravelZktecoAdms\Exceptions\DeviceLimitReachedException;
use Athwari\LaravelZktecoAdms\Exceptions\DeviceNotFoundException;
use Athwari\LaravelZktecoAdms\Exceptions\InvalidSerialNumberException;
use Athwari\LaravelZktecoAdms\Models\ZktecoDeviceEvent;
use Athwari\LaravelZktecoAdms\Services\DeviceManager;

function deviceManager(): DeviceManager
{
    return app(DeviceManager::class);
}

test('register new device', function () {
    $device = deviceManager()->registerDevice('TEST001');

    expect($device->serial_number)->toBe('TEST001')
        ->and($device->last_activity_at)->not->toBeNull();
});

test('register existing device returns same', function () {
    $first = deviceManager()->registerDevice('TEST001');
    $second = deviceManager()->registerDevice('TEST001');

    expect($second->id)->toBe($first->id);
});

test('reject invalid serial number', function () {
    deviceManager()->registerDevice('has space');
})->throws(InvalidSerialNumberException::class);

test('reject empty serial number', function () {
    deviceManager()->registerDevice('');
})->throws(InvalidSerialNumberException::class);

test('device limit enforced', function () {
    config()->set('zkteco-adms.device.max_devices', 2);

    deviceManager()->registerDevice('DEV001');
    deviceManager()->registerDevice('DEV002');

    deviceManager()->registerDevice('DEV003');
})->throws(DeviceLimitReachedException::class);

test('unlimited devices when zero', function () {
    config()->set('zkteco-adms.device.max_devices', 0);

    for ($i = 1; $i <= 5; $i++) {
        $device = deviceManager()->registerDevice("DEV{$i}");
        expect($device)->not->toBeNull();
    }
});

test('device is online after registration', function () {
    $device = deviceManager()->registerDevice('TEST001');

    expect($device->isOnline())->toBeTrue();
});

test('get device returns null for unknown', function () {
    expect(deviceManager()->getDevice('UNKNOWN'))->toBeNull();
});

test('device exists', function () {
    deviceManager()->registerDevice('TEST001');

    expect(deviceManager()->deviceExists('TEST001'))->toBeTrue()
        ->and(deviceManager()->deviceExists('UNKNOWN'))->toBeFalse();
});

test('list devices', function () {
    deviceManager()->registerDevice('DEV001');
    deviceManager()->registerDevice('DEV002');

    expect(deviceManager()->listDevices())->toHaveCount(2);
});

test('set device timezone', function () {
    deviceManager()->registerDevice('TEST001');
    deviceManager()->setDeviceTimezone('TEST001', 'Europe/Istanbul');

    expect(deviceManager()->getDeviceTimezone('TEST001'))->toBe('Europe/Istanbul');
});

test('register device uses default timezone when missing', function () {
    config()->set('zkteco-adms.default_timezone', 'Asia/Riyadh');

    $device = deviceManager()->registerDevice('TZDEFAULT001');

    expect($device->timezone)->toBe('Asia/Riyadh');
});

test('register device keeps provided timezone attribute', function () {
    $device = deviceManager()->registerDevice('TZCUSTOM001', null, ['timezone' => 'Europe/Istanbul']);

    expect($device->timezone)->toBe('Europe/Istanbul');
});

test('set timezone for unknown device throws', function () {
    deviceManager()->setDeviceTimezone('UNKNOWN', 'UTC');
})->throws(DeviceNotFoundException::class);

test('get timezone for unknown device falls back to default', function () {
    config()->set('zkteco-adms.default_timezone', 'Asia/Riyadh');

    expect(deviceManager()->getDeviceTimezone('UNKNOWN'))->toBe('Asia/Riyadh');
});

test('update device options', function () {
    deviceManager()->registerDevice('TEST001');
    deviceManager()->updateDeviceOptions('TEST001', ['FWVersion' => '1.0']);

    $device = deviceManager()->getDevice('TEST001');
    expect($device->options['FWVersion'])->toBe('1.0');

    // Merge test
    deviceManager()->updateDeviceOptions('TEST001', ['DeviceName' => 'TestDev']);
    $device = deviceManager()->getDevice('TEST001');
    expect($device->options['FWVersion'])->toBe('1.0')
        ->and($device->options['DeviceName'])->toBe('TestDev');
});

test('update device options and info for unknown device are no-ops', function () {
    deviceManager()->updateDeviceOptions('UNKNOWN', ['FWVersion' => '1.0']);
    deviceManager()->updateDeviceInfo('UNKNOWN', ['FWVersion' => '1.0']);
    deviceManager()->updateActivity('UNKNOWN', '127.0.0.1', ['model' => 'X']);

    expect(deviceManager()->deviceExists('UNKNOWN'))->toBeFalse();
});

test('update device info extracts known fields', function () {
    deviceManager()->registerDevice('TEST001');
    deviceManager()->updateDeviceInfo('TEST001', [
        'FWVersion' => 'Ver 8.1.1',
        'DeviceName' => 'SpeedFace-V5L',
        'Platform' => 'ZMM220_TDB400',
    ]);

    $device = deviceManager()->getDevice('TEST001');
    expect($device->firmware_version)->toBe('Ver 8.1.1')
        ->and($device->name)->toBe('SpeedFace-V5L')
        ->and($device->device_type)->toBe('ZMM220_TDB400');
});

test('device manager records device events when enabled', function () {
    config()->set('zkteco-adms.events.dispatch_device_event', true);

    $device = deviceManager()->registerDevice('EVENT001', '10.0.0.1');
    $device->update([
        'status' => 'offline',
        'last_activity_at' => now()->subMinutes(20),
    ]);

    deviceManager()->updateActivity('EVENT001', '10.0.0.2', ['model' => 'K40']);
    deviceManager()->updateDeviceInfo('EVENT001', ['FWVersion' => '2.0']);

    $eventTypes = ZktecoDeviceEvent::query()->pluck('event_type')->map->value->sort()->values()->all();

    expect(ZktecoDeviceEvent::query()->count())->toBe(3)
        ->and($eventTypes)
        ->toBe(['connected', 'info_received', 'registered']);
});

test('evict stale devices', function () {
    config()->set('zkteco-adms.device_eviction_timeout', 1);

    $device = deviceManager()->registerDevice('STALE001');
    $device->update(['last_activity_at' => now()->subSeconds(10)]);

    $count = deviceManager()->evictStaleDevices();

    expect($count)->toBe(1);
});

test('device snapshots', function () {
    deviceManager()->registerDevice('TEST001');
    $snapshots = deviceManager()->getDeviceSnapshots();

    expect($snapshots)->toHaveCount(1)
        ->and($snapshots[0]->serial)->toBe('TEST001')
        ->and($snapshots[0]->online)->toBeTrue();
});

test('auto register disabled throws not found', function () {
    config()->set('zkteco-adms.device.auto_register', false);

    deviceManager()->registerDevice('NEWDEV001');
})->throws(DeviceNotFoundException::class);
