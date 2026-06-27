<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\Exceptions;

use RuntimeException;

class CommandQueueFullException extends RuntimeException
{
    public function __construct(
        public string $serialNumber,
        public int $maxCommands,
    ) {
        parent::__construct("Command queue full for device '{$serialNumber}' (max: {$maxCommands})");
    }
}
