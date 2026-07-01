<?php

use Athwari\LaravelZktecoAdms\Exceptions\CommandQueueFullException;
use Athwari\LaravelZktecoAdms\Exceptions\DeviceNotFoundException;
use Athwari\LaravelZktecoAdms\Models\ZktecoDeviceCommand;
use Athwari\LaravelZktecoAdms\Models\ZktecoDeviceEvent;
use Athwari\LaravelZktecoAdms\Services\CommandManager;
use Athwari\LaravelZktecoAdms\Services\DeviceManager;

function commandManager(): CommandManager
{
    return app(CommandManager::class);
}

function commandDeviceManager(): DeviceManager
{
    return app(DeviceManager::class);
}

test('queue command', function () {
    commandDeviceManager()->registerDevice('TEST001');

    $id = commandManager()->queueCommand('TEST001', 'INFO');

    expect($id)->toBeGreaterThan(0)
        ->and(commandManager()->pendingCount('TEST001'))->toBe(1);
});

test('queue command for unknown device', function () {
    commandManager()->queueCommand('UNKNOWN', 'INFO');
})->throws(DeviceNotFoundException::class);

test('queue command limit', function () {
    config()->set('zkteco-adms.max_commands_per_device', 2);
    commandDeviceManager()->registerDevice('TEST001');

    commandManager()->queueCommand('TEST001', 'INFO');
    commandManager()->queueCommand('TEST001', 'CHECK');

    commandManager()->queueCommand('TEST001', 'REBOOT');
})->throws(CommandQueueFullException::class);

test('drain commands', function () {
    commandDeviceManager()->registerDevice('TEST001');
    commandManager()->queueCommand('TEST001', 'INFO');
    commandManager()->queueCommand('TEST001', 'CHECK');

    $entries = commandManager()->drainCommands('TEST001');

    expect($entries)->toHaveCount(2)
        ->and($entries[0]->command)->toBe('INFO')
        ->and($entries[1]->command)->toBe('CHECK')
        ->and($entries[0]->toWireFormat())->toMatch('/^C:\d+:INFO\n$/');
});

test('drain commands marks as sent', function () {
    commandDeviceManager()->registerDevice('TEST001');
    commandManager()->queueCommand('TEST001', 'INFO');

    commandManager()->drainCommands('TEST001');

    // No more pending (unsent) commands
    $drained = commandManager()->drainCommands('TEST001');

    expect($drained)->toHaveCount(0);
});

test('unknown device has no commands', function () {
    expect(commandManager()->drainCommands('UNKNOWN'))->toBe([])
        ->and(commandManager()->pendingCount('UNKNOWN'))->toBe(0);
});

test('confirm command', function () {
    commandDeviceManager()->registerDevice('TEST001');
    $id = commandManager()->queueCommand('TEST001', 'INFO');

    commandManager()->confirmCommand($id, 0);

    expect(commandManager()->getQueuedCommand($id))->toBe('INFO');
});

test('confirm command marks failures and ignores unknown ids', function () {
    commandDeviceManager()->registerDevice('TEST001');
    $id = commandManager()->queueCommand('TEST001', 'INFO');

    commandManager()->confirmCommand($id, 1);

    $commandModel = config('zkteco-adms.models.device_command', ZktecoDeviceCommand::class);
    $log = $commandModel::query()->where('command_id', $id)->firstOrFail();

    expect($log->status->value)->toBe('failed')
        ->and($log->response)->toBe('Return code: 1');

    commandManager()->confirmCommand(999999, 0);

    expect(commandManager()->getQueuedCommand(999999))->toBe('');
});

test('queue command records a device event when enabled', function () {
    config()->set('zkteco-adms.events.dispatch_device_event', true);
    commandDeviceManager()->registerDevice('TEST001');

    $id = commandManager()->queueCommand('TEST001', 'INFO');

    expect($id)->toBeGreaterThan(0)
        ->and(ZktecoDeviceEvent::query()->count())->toBeGreaterThanOrEqual(1)
        ->and(ZktecoDeviceEvent::query()->latest('created_at')->first()?->event_type->value)->toBe('command_sent');
});

test('confirm command records a device event when enabled', function () {
    config()->set('zkteco-adms.events.dispatch_device_event', true);
    commandDeviceManager()->registerDevice('TEST001');
    $id = commandManager()->queueCommand('TEST001', 'INFO');

    commandManager()->confirmCommand($id, 0);

    expect(ZktecoDeviceEvent::query()
        ->where('event_type', 'command_acknowledged')
        ->exists())->toBeTrue();
});

test('convenience commands', function () {
    commandDeviceManager()->registerDevice('TEST001');

    $id = commandManager()->sendInfoCommand('TEST001');
    expect($id)->toBeGreaterThan(0)
        ->and(commandManager()->getQueuedCommand($id))->toBe('INFO');

    $id = commandManager()->sendCheckCommand('TEST001');
    expect(commandManager()->getQueuedCommand($id))->toBe('CHECK');

    $id = commandManager()->sendQueryUsersCommand('TEST001');
    expect(commandManager()->getQueuedCommand($id))->toBe('DATA QUERY USERINFO');

    $id = commandManager()->sendRebootCommand('TEST001');
    expect(commandManager()->getQueuedCommand($id))->toBe('REBOOT');

    $id = commandManager()->sendClearLogsCommand('TEST001');
    expect(commandManager()->getQueuedCommand($id))->toBe('CLEAR LOG');

    $id = commandManager()->sendClearDataCommand('TEST001');
    expect(commandManager()->getQueuedCommand($id))->toBe('CLEAR DATA');
});

test('send user add command', function () {
    commandDeviceManager()->registerDevice('TEST001');

    $id = commandManager()->sendUserAddCommand('TEST001', '1001', 'John Doe', 0, '12345');
    $cmd = commandManager()->getQueuedCommand($id);

    expect($cmd)->toContain('DATA UPDATE USERINFO')
        ->toContain('PIN=1001')
        ->toContain('Name=John Doe');
});

test('send user delete command', function () {
    commandDeviceManager()->registerDevice('TEST001');

    $id = commandManager()->sendUserDeleteCommand('TEST001', '1001');
    $cmd = commandManager()->getQueuedCommand($id);

    expect($cmd)->toBe('DATA DELETE USERINFO PIN=1001');
});

test('reject command with newlines', function () {
    commandDeviceManager()->registerDevice('TEST001');
    commandManager()->queueCommand('TEST001', "INJECT\nC:999:EVIL");
})->throws(InvalidArgumentException::class);
