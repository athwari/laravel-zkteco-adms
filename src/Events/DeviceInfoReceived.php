<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DeviceInfoReceived
{
    use Dispatchable;

    /**
     * @param  array<string, string>  $info
     */
    public function __construct(
        public string $serialNumber,
        public array $info,
    ) {}
}
