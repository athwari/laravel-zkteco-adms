<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\Exceptions;

use RuntimeException;

class DeviceLimitReachedException extends RuntimeException
{
    public function __construct(public int $maxDevices)
    {
        parent::__construct("Device limit reached (max: {$maxDevices})");
    }
}
