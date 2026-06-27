<?php

use Athwari\LaravelZktecoAdms\Models\ZktecoDevice;
use Athwari\LaravelZktecoAdms\Services\DeviceManager;
use Athwari\LaravelZktecoAdms\Tests\TestCase;
use Illuminate\Testing\PendingCommand;

function runArtisanCommand(string $command, array $parameters = []): PendingCommand
{
    /** @var TestCase $testCase */
    $testCase = test();

    return $testCase->artisan($command, $parameters);
}

/** @var TestCase $this */
test('evict stale devices command respects disabled flag', function () {
    config()->set('zkteco-adms.device_eviction_enabled', false);

    runArtisanCommand('zkteco:evict-stale-devices')
        ->expectsOutput('Device eviction is disabled.')
        ->assertSuccessful();
});

test('evict stale devices command reports stale and clean runs', function () {
    config()->set('zkteco-adms.device_eviction_enabled', true);
    config()->set('zkteco-adms.device_eviction_timeout', 1);

    /** @var DeviceManager $deviceManager */
    $deviceManager = app(DeviceManager::class);
    $device = $deviceManager->registerDevice('STALE001');
    $device->update(['last_activity_at' => now()->subSeconds(10)]);

    runArtisanCommand('zkteco:evict-stale-devices')
        ->expectsOutput('Marked 1 stale device(s) as offline.')
        ->assertSuccessful();

    expect(ZktecoDevice::query()->findOrFail($device->id)->status->value)->toBe('offline');

    $device->update(['last_activity_at' => now()]);

    runArtisanCommand('zkteco:evict-stale-devices')
        ->expectsOutput('No stale devices found.')
        ->assertSuccessful();
});
