<?php

use Athwari\LaravelZktecoAdms\Facades\ZktecoAdms;
use Athwari\LaravelZktecoAdms\LaravelZktecoAdms;

test('facade resolves laravel zkteco adms instance', function () {
    $instance = ZktecoAdms::getFacadeRoot();

    expect($instance)->toBeInstanceOf(LaravelZktecoAdms::class)
        ->and($instance)->toBe(app(LaravelZktecoAdms::class));
});

test('facade can register and query a device', function () {
    ZktecoAdms::registerDevice('FACADE001');

    expect(ZktecoAdms::deviceExists('FACADE001'))->toBeTrue();
});

test('facade exposes command operations', function () {
    ZktecoAdms::registerDevice('FACADECMD001');
    $commandId = ZktecoAdms::sendCheckCommand('FACADECMD001');

    expect($commandId)->toBeInt()
        ->and($commandId)->toBeGreaterThan(0)
        ->and(ZktecoAdms::pendingCount('FACADECMD001'))->toBeGreaterThan(0)
        ->and(ZktecoAdms::getQueuedCommand($commandId))->toContain('CHECK');
});
