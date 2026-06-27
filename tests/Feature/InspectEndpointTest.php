<?php

use Athwari\LaravelZktecoAdms\Services\DeviceManager;
use Athwari\LaravelZktecoAdms\Tests\TestCase;

/** @var TestCase $this */
test('inspect disabled by default', function () {
    $this->get('/iclock/inspect?SN=TEST001')->assertStatus(404);
});

test('inspect returns json when enabled', function () {
    config()->set('zkteco-adms.enable_inspect', true);

    app(DeviceManager::class)->registerDevice('TEST001');

    $response = $this->get('/iclock/inspect?SN=TEST001');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/json');

    $data = json_decode((string) $response->getContent(), true);

    expect($data)
        ->toHaveKey('devices')
        ->toHaveKey('count')
        ->toHaveKey('time')
        ->and($data['count'])->toBe(1)
        ->and($data['devices'][0]['serial'])->toBe('TEST001');
});
