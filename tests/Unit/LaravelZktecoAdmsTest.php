<?php

use Athwari\LaravelZktecoAdms\LaravelZktecoAdms;
use Athwari\LaravelZktecoAdms\Services\CommandManager;
use Athwari\LaravelZktecoAdms\Services\DeviceCommandBuilder;

function laravelZktecoAdms(): LaravelZktecoAdms
{
    return app(LaravelZktecoAdms::class);
}

test('laravel zkteco adms delegates device operations', function () {
    config()->set('zkteco-adms.device_eviction_timeout', 1);

    $device = laravelZktecoAdms()->registerDevice('APP001', '10.0.0.1', ['language' => 'en']);

    laravelZktecoAdms()->updateActivity('APP001', '10.0.0.2', ['model' => 'SpeedFace']);
    laravelZktecoAdms()->setDeviceTimezone('APP001', 'Asia/Riyadh');
    laravelZktecoAdms()->updateDeviceOptions('APP001', ['FWVersion' => '1.0']);
    laravelZktecoAdms()->updateDeviceInfo('APP001', [
        'DeviceName' => 'Lobby Device',
        'Platform' => 'ZMM220',
        'PushVersion' => '2.4.1',
    ]);

    $fetched = laravelZktecoAdms()->getDevice('APP001');

    expect(laravelZktecoAdms()->deviceExists('APP001'))->toBeTrue()
        ->and(laravelZktecoAdms()->getDeviceTimezone('APP001'))->toBe('Asia/Riyadh')
        ->and(laravelZktecoAdms()->listDevices())->toHaveCount(1)
        ->and(laravelZktecoAdms()->getDeviceSnapshots())->toHaveCount(1)
        ->and($fetched)->not->toBeNull()
        ->and($fetched->ip_address)->toBe('10.0.0.2')
        ->and($fetched->model)->toBe('SpeedFace')
        ->and($fetched->name)->toBe('Lobby Device')
        ->and($fetched->device_type)->toBe('ZMM220')
        ->and($fetched->push_version)->toBe('2.4.1')
        ->and($fetched->options['FWVersion'])->toBe('1.0');

    $device->update(['last_activity_at' => now()->subSeconds(10)]);

    expect(laravelZktecoAdms()->evictStaleDevices())->toBe(1);
});

test('laravel zkteco adms delegates command operations and accessors', function () {
    laravelZktecoAdms()->registerDevice('APP002');

    $queued = laravelZktecoAdms()->queueCommand('APP002', 'INFO');
    $check = laravelZktecoAdms()->sendCheckCommand('APP002');
    $queryUsers = laravelZktecoAdms()->sendQueryUsersCommand('APP002');
    $clearLogs = laravelZktecoAdms()->sendClearLogsCommand('APP002');
    $clearData = laravelZktecoAdms()->sendClearDataCommand('APP002');
    $reboot = laravelZktecoAdms()->sendRebootCommand('APP002');
    $userAdd = laravelZktecoAdms()->sendUserAddCommand('APP002', '1002', 'John Doe', 0, 'CARD2');
    $userDelete = laravelZktecoAdms()->sendUserDeleteCommand('APP002', '1002');

    expect(laravelZktecoAdms()->pendingCount('APP002'))->toBe(8)
        ->and(laravelZktecoAdms()->getQueuedCommand($queued))->toBe('INFO')
        ->and(laravelZktecoAdms()->getQueuedCommand($check))->toBe('CHECK')
        ->and(laravelZktecoAdms()->getQueuedCommand($queryUsers))->toBe('DATA QUERY USERINFO')
        ->and(laravelZktecoAdms()->getQueuedCommand($clearLogs))->toBe('CLEAR LOG')
        ->and(laravelZktecoAdms()->getQueuedCommand($clearData))->toBe('CLEAR DATA')
        ->and(laravelZktecoAdms()->getQueuedCommand($reboot))->toBe('REBOOT')
        ->and(laravelZktecoAdms()->getQueuedCommand($userDelete))->toBe('DATA DELETE USERINFO PIN=1002')
        ->and(laravelZktecoAdms()->getQueuedCommand($userAdd))->toContain('DATA UPDATE USERINFO');

    $drained = laravelZktecoAdms()->drainCommands('APP002');
    laravelZktecoAdms()->confirmCommand($queued, 0);

    expect($drained)->toHaveCount(8)
        ->and($drained[0]->command)->toBe('INFO')
        ->and(laravelZktecoAdms()->commandManager())->toBeInstanceOf(CommandManager::class)
        ->and(laravelZktecoAdms()->commandBuilder())->toBeInstanceOf(DeviceCommandBuilder::class);
});
