<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\DTOs;

/**
 * Represents the result of a command execution reported by a device
 * via the /iclock/devicecmd endpoint.
 *
 * After the server sends "C:<ID>:<CMD>\n" via /iclock/getrequest, the device
 * executes the command and POSTs back a confirmation containing the command ID
 * and a return code. A returnCode of 0 indicates success.
 */
final class CommandResult
{
    public function __construct(
        /** The device that executed the command. */
        public string $serialNumber,
        /** The command identifier assigned by the server. */
        public int $id,
        /** The device's result code (0 = success). */
        public int $returnCode,
        /** The command type echoed back by the device (e.g. "DATA", "INFO"). */
        public string $command,
        /**
         * The original command string that was queued.
         * Enables callers to correlate a device's "CMD=DATA" confirmation
         * back to the specific operation that triggered it.
         * Empty if the command ID is not found in the server's pending map.
         */
        public string $queuedCommand = '',
    ) {}

    /**
     * Whether the command executed successfully.
     */
    public function isSuccess(): bool
    {
        return $this->returnCode === 0;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'serial_number' => $this->serialNumber,
            'id' => $this->id,
            'return_code' => $this->returnCode,
            'command' => $this->command,
            'queued_command' => $this->queuedCommand,
            'success' => $this->isSuccess(),
        ];
    }
}
