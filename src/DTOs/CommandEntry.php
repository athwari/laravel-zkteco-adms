<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\DTOs;

/**
 * Pairs a pre-assigned command ID with the command string.
 *
 * IDs are assigned at queue time so callers can correlate
 * command confirmations back to the original request.
 */
final class CommandEntry
{
    public function __construct(
        /** Monotonically increasing identifier assigned at queue time. */
        public int $id,
        /** Raw command string (e.g. "DATA UPDATE USERINFO PIN=1\tName=John"). */
        public string $command,
    ) {}

    /**
     * Format this entry for the ADMS wire protocol.
     *
     * The device expects "C:<ID>:<CMD>\n" where ID is a monotonically
     * increasing integer used to correlate command confirmations.
     */
    public function toWireFormat(): string
    {
        return sprintf("C:%d:%s\n", $this->id, $this->command);
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'command' => $this->command,
        ];
    }
}
