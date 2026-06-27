<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\Events;

use Athwari\LaravelZktecoAdms\DTOs\CommandResult;
use Illuminate\Foundation\Events\Dispatchable;

class CommandResultReceived
{
    use Dispatchable;

    public function __construct(
        public CommandResult $result,
    ) {}
}
