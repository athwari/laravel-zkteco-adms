<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DeviceRegistered
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public string $serialNumber,
        public array $options = [],
    ) {}
}
