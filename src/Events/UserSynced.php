<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\Events;

use Athwari\LaravelZktecoAdms\Models\ZktecoDevice;
use Athwari\LaravelZktecoAdms\Models\ZktecoUser;
use Illuminate\Foundation\Events\Dispatchable;

class UserSynced
{
    use Dispatchable;

    public function __construct(
        public ZktecoUser $user,
        public ZktecoDevice $device,
    ) {}
}
