<?php

use Athwari\LaravelZktecoAdms\Services\CommandManager;
use Athwari\LaravelZktecoAdms\Services\DeviceManager;
use Athwari\LaravelZktecoAdms\Tests\TestCase;

/** @var TestCase $this */
test('getrequest returns ok when no commands', function () {
    $this->get('/iclock/getrequest?SN=TEST001')
        ->assertStatus(200)
        ->assertSee('OK');
});

test('getrequest returns pending commands', function () {
    $deviceManager = app(DeviceManager::class);
    $commandManager = app(CommandManager::class);

    $deviceManager->registerDevice('TEST001');
    $commandManager->queueCommand('TEST001', 'INFO');
    $commandManager->queueCommand('TEST001', 'CHECK');

    $response = $this->get('/iclock/getrequest?SN=TEST001');

    $response->assertStatus(200);
    $content = $response->getContent();

    expect($content)->toContain('INFO')
        ->toContain('CHECK')
        ->toMatch('/C:\d+:INFO/');
});

test('getrequest drains commands', function () {
    $deviceManager = app(DeviceManager::class);
    $commandManager = app(CommandManager::class);

    $deviceManager->registerDevice('TEST001');
    $commandManager->queueCommand('TEST001', 'INFO');

    // First call drains
    $response1 = $this->get('/iclock/getrequest?SN=TEST001');
    expect($response1->getContent())->toContain('INFO');

    // Second call returns OK (no more pending)
    $response2 = $this->get('/iclock/getrequest?SN=TEST001');
    expect($response2->getContent())->toBe('OK');
});

test('getrequest missing sn', function () {
    $this->get('/iclock/getrequest')->assertStatus(400);
});
