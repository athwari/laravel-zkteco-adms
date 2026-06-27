<?php

use Athwari\LaravelZktecoAdms\Services\CommandManager;
use Athwari\LaravelZktecoAdms\Services\DeviceCommandBuilder;
use Athwari\LaravelZktecoAdms\Services\DeviceManager;

function builderDeviceManager(): DeviceManager
{
    return app(DeviceManager::class);
}

function builderCommandManager(): CommandManager
{
    return app(CommandManager::class);
}

function deviceCommandBuilder(): DeviceCommandBuilder
{
    return app(DeviceCommandBuilder::class);
}

beforeEach(function () {
    builderDeviceManager()->registerDevice('BUILDER001');
});

test('device command builder queues every supported command type', function () {
    $info = deviceCommandBuilder()->getInfo('BUILDER001');
    $check = deviceCommandBuilder()->checkConnection('BUILDER001');
    $reboot = deviceCommandBuilder()->reboot('BUILDER001');
    $clearLogs = deviceCommandBuilder()->clearLogs('BUILDER001');
    $clearData = deviceCommandBuilder()->clearData('BUILDER001');
    $queryUsers = deviceCommandBuilder()->queryUsers('BUILDER001');
    $addUser = deviceCommandBuilder()->addUser('BUILDER001', '1001', 'Jane Doe', 14, 'CARD1');
    $deleteUser = deviceCommandBuilder()->deleteUser('BUILDER001', '1001');
    $raw = deviceCommandBuilder()->rawCommand('BUILDER001', 'CUSTOM CMD');

    expect(builderCommandManager()->getQueuedCommand($info))->toBe('INFO')
        ->and(builderCommandManager()->getQueuedCommand($check))->toBe('CHECK')
        ->and(builderCommandManager()->getQueuedCommand($reboot))->toBe('REBOOT')
        ->and(builderCommandManager()->getQueuedCommand($clearLogs))->toBe('CLEAR LOG')
        ->and(builderCommandManager()->getQueuedCommand($clearData))->toBe('CLEAR DATA')
        ->and(builderCommandManager()->getQueuedCommand($queryUsers))->toBe('DATA QUERY USERINFO')
        ->and(builderCommandManager()->getQueuedCommand($deleteUser))->toBe('DATA DELETE USERINFO PIN=1001')
        ->and(builderCommandManager()->getQueuedCommand($raw))->toBe('CUSTOM CMD')
        ->and(builderCommandManager()->getQueuedCommand($addUser))->toContain('DATA UPDATE USERINFO')
        ->and(builderCommandManager()->getQueuedCommand($addUser))->toContain('PIN=1001')
        ->and(builderCommandManager()->getQueuedCommand($addUser))->toContain('Name=Jane Doe')
        ->and(builderCommandManager()->getQueuedCommand($addUser))->toContain('Privilege=14')
        ->and(builderCommandManager()->getQueuedCommand($addUser))->toContain('Card=CARD1');
});
