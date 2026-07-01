<?php

declare(strict_types=1);

use Athwari\LaravelZktecoAdms\Events\DeviceRegistered;
use Athwari\LaravelZktecoAdms\Services\DeviceManager;
use Athwari\LaravelZktecoAdms\Tests\TestCase;
use Illuminate\Support\Facades\Event;

test('registry get', function () {
    /** @var TestCase $this */
    $this->get('/iclock/registry?SN=TEST001')
        ->assertStatus(200)
        ->assertSee('OK');
});

test('registry requires serial number', function () {
    /** @var TestCase $this */
    $this->get('/iclock/registry')->assertStatus(400);
});

test('registry post with body', function () {
    /** @var TestCase $this */
    Event::fake([DeviceRegistered::class]);

    $body = '~DeviceName=SpeedFace,~FWVersion=Ver 1.1.17,~MACAddress=AA:BB:CC:DD:EE:FF';
    $response = $this->call('POST', '/iclock/registry?SN=TEST001', [], [], [], [], $body);

    $response->assertStatus(200);
    $response->assertSee('OK');

    Event::assertDispatched(DeviceRegistered::class, fn ($event) => $event->serialNumber === 'TEST001'
        && $event->options['DeviceName'] === 'SpeedFace'
        && $event->options['FWVersion'] === 'Ver 1.1.17');
});

test('registry updates device options', function () {
    /** @var TestCase $this */
    $body = '~DeviceName=SpeedFace,~FWVersion=Ver 1.0';
    $this->call('POST', '/iclock/registry?SN=TEST001', [], [], [], [], $body);

    $device = app(DeviceManager::class)->getDevice('TEST001');

    expect($device->options['DeviceName'])->toBe('SpeedFace');
});
