<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\Events;

use Athwari\LaravelZktecoAdms\DTOs\AttendanceRecord;
use Illuminate\Foundation\Events\Dispatchable;

class AttendanceReceived
{
    use Dispatchable;

    /**
     * @param  array<int, AttendanceRecord>  $records
     */
    public function __construct(
        public string $serialNumber,
        public array $records,
    ) {}
}
