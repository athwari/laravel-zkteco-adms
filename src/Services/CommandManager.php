<?php

namespace Athwari\LaravelZktecoAdms\Services;

use Athwari\LaravelZktecoAdms\DTOs\CommandEntry;
use Athwari\LaravelZktecoAdms\Enums\CommandStatus;
use Athwari\LaravelZktecoAdms\Enums\DeviceEventType;
use Athwari\LaravelZktecoAdms\Exceptions\CommandQueueFullException;
use Athwari\LaravelZktecoAdms\Exceptions\DeviceNotFoundException;
use Athwari\LaravelZktecoAdms\Models\ZktecoDevice;
use Athwari\LaravelZktecoAdms\Models\ZktecoDeviceCommand;
use Athwari\LaravelZktecoAdms\Models\ZktecoDeviceEvent;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Manages command queuing and execution for ZKTeco devices.
 *
 * Commands are queued in the database and delivered to devices when
 * they poll via GET /iclock/getrequest or GET /iclock/cdata.
 * Wire format: "C:<ID>:<CMD>\n"
 */
class CommandManager
{
    /**
     * Queue a command to be sent to a device on its next poll.
     *
     * @return int The assigned command ID
     *
     * @throws DeviceNotFoundException If the device is not registered
     * @throws CommandQueueFullException If the per-device queue limit is reached
     * @throws InvalidArgumentException If the command contains control characters
     */
    public function queueCommand(string $serialNumber, string $command): int
    {
        $this->validateCommandField('command', $command);

        $deviceModel = config('zkteco-adms.models.device', ZktecoDevice::class);
        $device = $deviceModel::where('serial_number', $serialNumber)->first();

        if (! $device) {
            throw new DeviceNotFoundException($serialNumber);
        }

        $maxCommands = config('zkteco-adms.max_commands_per_device', 100);
        if ($maxCommands > 0) {
            $pendingCount = $this->pendingCount($serialNumber);
            if ($pendingCount >= $maxCommands) {
                throw new CommandQueueFullException($serialNumber, $maxCommands);
            }
        }

        $commandModel = config('zkteco-adms.models.device_command', ZktecoDeviceCommand::class);

        $log = $commandModel::create([
            'device_id' => $device->id,
            'command_id' => 0,
            'command_type' => $this->inferCommandType($command),
            'command_content' => $command,
            'status' => CommandStatus::Pending,
            'queued_at' => now(),
        ]);

        $log->update(['command_id' => $log->id]);

        if (config('zkteco-adms.events.dispatch_device_event', true)) {
            ZktecoDeviceEvent::record(
                $device->id,
                DeviceEventType::CommandSent,
                ['command_id' => $log->id, 'command' => $command]
            );
        }

        Log::debug('Command queued', [
            'device' => $serialNumber,
            'command_id' => $log->id,
            'command' => $command,
        ]);

        return (int) $log->id;
    }

    /**
     * Drain all pending commands for a device and mark them as sent.
     *
     * @return CommandEntry[]
     */
    public function drainCommands(string $serialNumber): array
    {
        $deviceModel = config('zkteco-adms.models.device', ZktecoDevice::class);
        $device = $deviceModel::where('serial_number', $serialNumber)->first();

        if (! $device) {
            return [];
        }

        $commandModel = config('zkteco-adms.models.device_command', ZktecoDeviceCommand::class);

        $logs = $commandModel::where('device_id', $device->id)
            ->where('status', CommandStatus::Pending)
            ->orderBy('id')
            ->get();

        $entries = [];
        foreach ($logs as $log) {
            $log->markAsSent();

            $entries[] = new CommandEntry(
                id: (int) $log->command_id,
                command: $log->command_content,
            );
        }

        return $entries;
    }

    /**
     * Get the number of pending commands for a device.
     */
    public function pendingCount(string $serialNumber): int
    {
        $deviceModel = config('zkteco-adms.models.device', ZktecoDevice::class);
        $device = $deviceModel::where('serial_number', $serialNumber)->first();

        if (! $device) {
            return 0;
        }

        $commandModel = config('zkteco-adms.models.device_command', ZktecoDeviceCommand::class);

        return $commandModel::where('device_id', $device->id)
            ->whereIn('status', [CommandStatus::Pending, CommandStatus::Sent])
            ->count();
    }

    /**
     * Mark a command as acknowledged by the device.
     */
    public function confirmCommand(int $commandId, int $returnCode): void
    {
        $commandModel = config('zkteco-adms.models.device_command', ZktecoDeviceCommand::class);

        $log = $commandModel::where('command_id', $commandId)->first();

        if ($log === null) {
            Log::warning('Command confirmation for unknown ID', ['command_id' => $commandId]);

            return;
        }

        if ($returnCode === 0) {
            $log->markAsAcknowledged(null, $returnCode);
        } else {
            $log->markAsFailed("Return code: {$returnCode}");
        }

        if (config('zkteco-adms.events.dispatch_device_event', true)) {
            ZktecoDeviceEvent::record(
                $log->device_id,
                DeviceEventType::CommandAcknowledged,
                ['command_id' => $commandId, 'return_code' => $returnCode]
            );
        }
    }

    /**
     * Get the original command string for a given command ID.
     */
    public function getQueuedCommand(int $commandId): string
    {
        $commandModel = config('zkteco-adms.models.device_command', ZktecoDeviceCommand::class);
        $log = $commandModel::where('command_id', $commandId)->first();

        return $log->command_content ?? '';
    }

    // ---------------------------------------------------------------
    // Convenience command methods
    // ---------------------------------------------------------------

    /** Queue an INFO command to request device information. */
    public function sendInfoCommand(string $serialNumber): int
    {
        return $this->queueCommand($serialNumber, 'INFO');
    }

    /** Queue a CHECK (heartbeat) command. */
    public function sendCheckCommand(string $serialNumber): int
    {
        return $this->queueCommand($serialNumber, 'CHECK');
    }

    /**
     * Queue a DATA UPDATE USERINFO command.
     *
     * Wire format: DATA UPDATE USERINFO PIN=<pin>\tName=<name>\tPrivilege=<priv>\tCard=<card>
     */
    public function sendUserAddCommand(
        string $serialNumber,
        string $pin,
        string $name,
        int $privilege = 0,
        string $card = '',
    ): int {
        foreach (['pin' => $pin, 'name' => $name, 'card' => $card] as $field => $value) {
            $this->validateCommandField($field, $value);
        }

        $cmd = sprintf("DATA UPDATE USERINFO PIN=%s\tName=%s\tPrivilege=%d\tCard=%s", $pin, $name, $privilege, $card);

        return $this->queueCommand($serialNumber, $cmd);
    }

    /** Queue a DATA DELETE USERINFO command. */
    public function sendUserDeleteCommand(string $serialNumber, string $pin): int
    {
        $this->validateCommandField('pin', $pin);

        return $this->queueCommand($serialNumber, sprintf('DATA DELETE USERINFO PIN=%s', $pin));
    }

    /** Queue a DATA QUERY USERINFO command. */
    public function sendQueryUsersCommand(string $serialNumber): int
    {
        return $this->queueCommand($serialNumber, 'DATA QUERY USERINFO');
    }

    /** Queue a CLEAR LOG command. */
    public function sendClearLogsCommand(string $serialNumber): int
    {
        return $this->queueCommand($serialNumber, 'CLEAR LOG');
    }

    /** Queue a CLEAR DATA command. */
    public function sendClearDataCommand(string $serialNumber): int
    {
        return $this->queueCommand($serialNumber, 'CLEAR DATA');
    }

    /** Queue a REBOOT command. */
    public function sendRebootCommand(string $serialNumber): int
    {
        return $this->queueCommand($serialNumber, 'REBOOT');
    }

    /**
     * Infer the command type from the command string.
     */
    private function inferCommandType(string $command): string
    {
        $upper = strtoupper(trim($command));

        if (str_starts_with($upper, 'INFO')) {
            return 'INFO';
        }
        if (str_starts_with($upper, 'CHECK')) {
            return 'CHECK';
        }
        if (str_starts_with($upper, 'REBOOT')) {
            return 'REBOOT';
        }
        if (str_starts_with($upper, 'CLEAR')) {
            return 'CLEAR';
        }

        return 'DATA';
    }

    /**
     * Validate that a command field does not contain control characters.
     *
     * @throws InvalidArgumentException
     */
    private function validateCommandField(string $name, string $value): void
    {
        if (str_contains($value, "\r") || str_contains($value, "\n")) {
            throw new InvalidArgumentException(
                "Command field '{$name}' contains forbidden control characters."
            );
        }
    }
}
