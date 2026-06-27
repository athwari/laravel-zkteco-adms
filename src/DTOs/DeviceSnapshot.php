<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\DTOs;

/**
 * JSON representation of a device for the /iclock/inspect endpoint.
 */
final class DeviceSnapshot
{
    public function __construct(
        public string $serial,
        public string $lastActivity,
        public bool $online,
        public array $options,
        public string $timezone,
        public int $pendingCommands = 0,
    ) {}

    /**
     * Convert to array representation for JSON encoding.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'serial' => $this->serial,
            'last_activity' => $this->lastActivity,
            'online' => $this->online,
            'options' => $this->options,
            'timezone' => $this->timezone,
            'pending_commands' => $this->pendingCommands,
        ];
    }
}
