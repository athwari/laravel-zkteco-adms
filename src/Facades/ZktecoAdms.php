<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\Facades;

use Athwari\LaravelZktecoAdms\LaravelZktecoAdms;
use Athwari\LaravelZktecoAdms\Services\CommandManager;
use Athwari\LaravelZktecoAdms\Services\DeviceCommandBuilder;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Athwari\LaravelZktecoAdms\Models\ZktecoDevice registerDevice(string $serialNumber, ?string $ipAddress = null, array $attributes = [])
 * @method static void updateActivity(string $serialNumber, ?string $ipAddress = null, array $attributes = [])
 * @method static \Athwari\LaravelZktecoAdms\Models\ZktecoDevice|null getDevice(string $serialNumber)
 * @method static bool deviceExists(string $serialNumber)
 * @method static void setDeviceTimezone(string $serialNumber, string $timezone)
 * @method static string getDeviceTimezone(string $serialNumber)
 * @method static void updateDeviceOptions(string $serialNumber, array $options)
 * @method static void updateDeviceInfo(string $serialNumber, array $info)
 * @method static \Illuminate\Support\Collection listDevices()
 * @method static int evictStaleDevices()
 * @method static array getDeviceSnapshots()
 * @method static int queueCommand(string $serialNumber, string $command)
 * @method static array drainCommands(string $serialNumber)
 * @method static int pendingCount(string $serialNumber)
 * @method static void confirmCommand(int $commandId, int $returnCode)
 * @method static string getQueuedCommand(int $commandId)
 * @method static int sendInfoCommand(string $serialNumber)
 * @method static int sendCheckCommand(string $serialNumber)
 * @method static int sendUserAddCommand(string $serialNumber, string $pin, string $name, int $privilege = 0, string $card = '')
 * @method static int sendUserDeleteCommand(string $serialNumber, string $pin)
 * @method static int sendQueryUsersCommand(string $serialNumber)
 * @method static int sendClearLogsCommand(string $serialNumber)
 * @method static int sendClearDataCommand(string $serialNumber)
 * @method static int sendRebootCommand(string $serialNumber)
 * @method static CommandManager commandManager()
 * @method static DeviceCommandBuilder commandBuilder()
 *
 * @see LaravelZktecoAdms
 */
class ZktecoAdms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LaravelZktecoAdms::class;
    }
}
