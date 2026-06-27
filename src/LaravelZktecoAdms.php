<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms;

use Athwari\LaravelZktecoAdms\DTOs\CommandEntry;
use Athwari\LaravelZktecoAdms\DTOs\DeviceSnapshot;
use Athwari\LaravelZktecoAdms\Models\ZktecoDevice;
use Athwari\LaravelZktecoAdms\Services\CommandManager;
use Athwari\LaravelZktecoAdms\Services\DeviceCommandBuilder;
use Athwari\LaravelZktecoAdms\Services\DeviceManager;
use Illuminate\Support\Collection;

class LaravelZktecoAdms
{
    public function __construct(
        private readonly DeviceManager $deviceManager,
        private readonly CommandManager $commandManager,
        private readonly DeviceCommandBuilder $commandBuilder,
    ) {}

    public function registerDevice(string $serialNumber, ?string $ipAddress = null, array $attributes = []): ZktecoDevice
    {
        return $this->deviceManager->registerDevice($serialNumber, $ipAddress, $attributes);
    }

    public function updateActivity(string $serialNumber, ?string $ipAddress = null, array $attributes = []): void
    {
        $this->deviceManager->updateActivity($serialNumber, $ipAddress, $attributes);
    }

    public function getDevice(string $serialNumber): ?ZktecoDevice
    {
        return $this->deviceManager->getDevice($serialNumber);
    }

    public function deviceExists(string $serialNumber): bool
    {
        return $this->deviceManager->deviceExists($serialNumber);
    }

    public function setDeviceTimezone(string $serialNumber, string $timezone): void
    {
        $this->deviceManager->setDeviceTimezone($serialNumber, $timezone);
    }

    public function getDeviceTimezone(string $serialNumber): string
    {
        return $this->deviceManager->getDeviceTimezone($serialNumber);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function updateDeviceOptions(string $serialNumber, array $options): void
    {
        $this->deviceManager->updateDeviceOptions($serialNumber, $options);
    }

    /**
     * @param  array<string, string>  $info
     */
    public function updateDeviceInfo(string $serialNumber, array $info): void
    {
        $this->deviceManager->updateDeviceInfo($serialNumber, $info);
    }

    /**
     * @return Collection<int, ZktecoDevice>
     */
    public function listDevices(): Collection
    {
        return $this->deviceManager->listDevices();
    }

    public function evictStaleDevices(): int
    {
        return $this->deviceManager->evictStaleDevices();
    }

    /**
     * @return DeviceSnapshot[]
     */
    public function getDeviceSnapshots(): array
    {
        return $this->deviceManager->getDeviceSnapshots();
    }

    public function queueCommand(string $serialNumber, string $command): int
    {
        return $this->commandManager->queueCommand($serialNumber, $command);
    }

    /**
     * @return CommandEntry[]
     */
    public function drainCommands(string $serialNumber): array
    {
        return $this->commandManager->drainCommands($serialNumber);
    }

    public function pendingCount(string $serialNumber): int
    {
        return $this->commandManager->pendingCount($serialNumber);
    }

    public function confirmCommand(int $commandId, int $returnCode): void
    {
        $this->commandManager->confirmCommand($commandId, $returnCode);
    }

    public function getQueuedCommand(int $commandId): string
    {
        return $this->commandManager->getQueuedCommand($commandId);
    }

    public function sendInfoCommand(string $serialNumber): int
    {
        return $this->commandManager->sendInfoCommand($serialNumber);
    }

    public function sendCheckCommand(string $serialNumber): int
    {
        return $this->commandManager->sendCheckCommand($serialNumber);
    }

    public function sendUserAddCommand(
        string $serialNumber,
        string $pin,
        string $name,
        int $privilege = 0,
        string $card = '',
    ): int {
        return $this->commandManager->sendUserAddCommand($serialNumber, $pin, $name, $privilege, $card);
    }

    public function sendUserDeleteCommand(string $serialNumber, string $pin): int
    {
        return $this->commandManager->sendUserDeleteCommand($serialNumber, $pin);
    }

    public function sendQueryUsersCommand(string $serialNumber): int
    {
        return $this->commandManager->sendQueryUsersCommand($serialNumber);
    }

    public function sendClearLogsCommand(string $serialNumber): int
    {
        return $this->commandManager->sendClearLogsCommand($serialNumber);
    }

    public function sendClearDataCommand(string $serialNumber): int
    {
        return $this->commandManager->sendClearDataCommand($serialNumber);
    }

    public function sendRebootCommand(string $serialNumber): int
    {
        return $this->commandManager->sendRebootCommand($serialNumber);
    }

    public function commandManager(): CommandManager
    {
        return $this->commandManager;
    }

    public function commandBuilder(): DeviceCommandBuilder
    {
        return $this->commandBuilder;
    }
}
