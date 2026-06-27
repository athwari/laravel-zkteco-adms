<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\Exceptions;

use RuntimeException;

class DeviceNotFoundException extends RuntimeException
{
    public function __construct(public string $serialNumber)
    {
        parent::__construct("Device not found: '{$serialNumber}'");
    }
}
