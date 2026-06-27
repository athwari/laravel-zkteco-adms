<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\Console;

use Athwari\LaravelZktecoAdms\Services\DeviceManager;
use Illuminate\Console\Command;

class EvictStaleDevicesCommand extends Command
{
    protected $signature = 'zkteco:evict-stale-devices';

    protected $description = 'Mark stale ZKTeco devices as offline based on the configured eviction timeout';

    public function handle(DeviceManager $deviceManager): int
    {
        if (! config('zkteco-adms.device_eviction_enabled', true)) {
            $this->info('Device eviction is disabled.');

            return self::SUCCESS;
        }

        $count = $deviceManager->evictStaleDevices();

        if ($count > 0) {
            $this->info("Marked {$count} stale device(s) as offline.");
        } else {
            $this->info('No stale devices found.');
        }

        return self::SUCCESS;
    }
}
