<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\Exceptions;

use RuntimeException;

class InvalidSerialNumberException extends RuntimeException
{
    public function __construct(
        public string $serialNumber,
        string $reason = '',
    ) {
        $message = "Invalid serial number: '{$serialNumber}'";
        if ($reason !== '') {
            $message .= " ({$reason})";
        }
        parent::__construct($message);
    }
}
