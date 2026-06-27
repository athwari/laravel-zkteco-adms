<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\Events;

use Athwari\LaravelZktecoAdms\Models\ZktecoDevice;
use Illuminate\Foundation\Events\Dispatchable;

class DeviceConnected
{
    use Dispatchable;

    public function __construct(
        public ZktecoDevice $device,
    ) {}
}
