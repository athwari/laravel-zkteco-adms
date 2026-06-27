<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\Events;

use Athwari\LaravelZktecoAdms\DTOs\UserRecord;
use Illuminate\Foundation\Events\Dispatchable;

class UserQueryReceived
{
    use Dispatchable;

    /**
     * @param  array<int, UserRecord>  $users
     */
    public function __construct(
        public string $serialNumber,
        public array $users,
    ) {}
}
