<?php

use Athwari\LaravelZktecoAdms\Events\CommandResultReceived;
use Athwari\LaravelZktecoAdms\Services\CommandManager;
use Athwari\LaravelZktecoAdms\Services\DeviceManager;
use Athwari\LaravelZktecoAdms\Tests\TestCase;
use Illuminate\Support\Facades\Event;

/** @var TestCase $this */
test('devicecmd processes result', function () {
    Event::fake([CommandResultReceived::class]);

    $deviceManager = app(DeviceManager::class);
    $commandManager = app(CommandManager::class);

    $deviceManager->registerDevice('TEST001');
    $cmdId = $commandManager->sendInfoCommand('TEST001');

    $body = "ID={$cmdId}&Return=0&CMD=INFO";
    $response = $this->call('POST', '/iclock/devicecmd?SN=TEST001', [], [], [], [], $body);

    $response->assertStatus(200);
    $response->assertSee('OK');

    Event::assertDispatched(CommandResultReceived::class, fn ($event) => $event->result->id === $cmdId
        && $event->result->returnCode === 0
        && $event->result->command === 'INFO');
});

test('devicecmd handles batched results', function () {
    Event::fake([CommandResultReceived::class]);

    $deviceManager = app(DeviceManager::class);
    $commandManager = app(CommandManager::class);

    $deviceManager->registerDevice('TEST001');
    $id1 = $commandManager->sendInfoCommand('TEST001');
    $id2 = $commandManager->sendCheckCommand('TEST001');

    $body = "ID={$id1}&Return=0&CMD=INFO\nID={$id2}&Return=0&CMD=CHECK";
    $response = $this->call('POST', '/iclock/devicecmd?SN=TEST001', [], [], [], [], $body);

    $response->assertStatus(200);
    Event::assertDispatched(CommandResultReceived::class, 2);
});

test('devicecmd rejects get', function () {
    $this->get('/iclock/devicecmd?SN=TEST001')->assertStatus(405);
});

test('devicecmd requires serial number', function () {
    /** @var TestCase $this */
    $this->post('/iclock/devicecmd')->assertStatus(400);
});

test('devicecmd enriches result with queued command', function () {
    Event::fake([CommandResultReceived::class]);

    $deviceManager = app(DeviceManager::class);
    $commandManager = app(CommandManager::class);

    $deviceManager->registerDevice('TEST001');
    $cmdId = $commandManager->sendUserAddCommand('TEST001', '1001', 'John', 0, '');

    $body = "ID={$cmdId}&Return=0&CMD=DATA";
    $this->call('POST', '/iclock/devicecmd?SN=TEST001', [], [], [], [], $body);

    Event::assertDispatched(CommandResultReceived::class, fn ($event) => str_contains((string) $event->result->queuedCommand, 'DATA UPDATE USERINFO'));
});
