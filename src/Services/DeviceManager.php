<?php

namespace Athwari\LaravelZktecoAdms\Services;

use Athwari\LaravelZktecoAdms\DTOs\DeviceSnapshot;
use Athwari\LaravelZktecoAdms\Enums\DeviceEventType;
use Athwari\LaravelZktecoAdms\Enums\DeviceStatus;
use Athwari\LaravelZktecoAdms\Exceptions\DeviceLimitReachedException;
use Athwari\LaravelZktecoAdms\Exceptions\DeviceNotFoundException;
use Athwari\LaravelZktecoAdms\Exceptions\InvalidSerialNumberException;
use Athwari\LaravelZktecoAdms\Models\ZktecoDevice;
use Athwari\LaravelZktecoAdms\Models\ZktecoDeviceEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DeviceManager
{
    public function __construct(
        private readonly AttendanceParser $parser,
    ) {}

    /**
     * Register a device or return an existing one.
     */
    public function registerDevice(string $serialNumber, ?string $ipAddress = null, array $attributes = []): ZktecoDevice
    {
        if (! $this->parser->validateSerialNumber($serialNumber)) {
            throw new InvalidSerialNumberException($serialNumber, 'contains invalid characters');
        }

        $modelClass = config('zkteco-adms.models.device', ZktecoDevice::class);

        $device = $modelClass::where('serial_number', $serialNumber)->first();

        if ($device === null) {
            if (! config('zkteco-adms.device.auto_register', true)) {
                throw new DeviceNotFoundException($serialNumber);
            }

            $maxDevices = config('zkteco-adms.device.max_devices', 1000);
            if ($maxDevices > 0 && $modelClass::count() >= $maxDevices) {
                throw new DeviceLimitReachedException($maxDevices);
            }

            $deviceAttributes = array_filter($attributes);
            if (! isset($deviceAttributes['timezone'])) {
                $deviceAttributes['timezone'] = config('zkteco-adms.default_timezone', 'UTC');
            }

            $device = $modelClass::create(array_merge([
                'serial_number' => $serialNumber,
                'name' => "Device {$serialNumber}",
                'ip_address' => $ipAddress,
                'status' => DeviceStatus::Online,
                'last_activity_at' => now(),
                'options' => [],
            ], $deviceAttributes));

            if (config('zkteco-adms.events.dispatch_device_event', true)) {
                ZktecoDeviceEvent::record(
                    $device->id,
                    DeviceEventType::Registered,
                    ['serial_number' => $serialNumber],
                    $ipAddress
                );
            }

            Log::info('Device registered', ['device' => $serialNumber]);
        }

        return $device;
    }

    /**
     * Update a device's activity timestamp and mark it as online.
     */
    public function updateActivity(string $serialNumber, ?string $ipAddress = null, array $attributes = []): void
    {
        $modelClass = config('zkteco-adms.models.device', ZktecoDevice::class);
        $device = $modelClass::where('serial_number', $serialNumber)->first();

        if ($device === null) {
            return;
        }

        $wasOnline = $device->isOnline();
        $device->markAsOnline();

        $updateData = [];

        if ($ipAddress && $device->ip_address !== $ipAddress) {
            $updateData['ip_address'] = $ipAddress;
        }

        foreach (array_filter($attributes) as $key => $value) {
            if ($device->{$key} !== $value) {
                $updateData[$key] = $value;
            }
        }

        if ($updateData !== []) {
            $device->update($updateData);
        }

        if (! $wasOnline) {
            if (config('zkteco-adms.events.dispatch_device_event', true)) {
                ZktecoDeviceEvent::record(
                    $device->id,
                    DeviceEventType::Connected,
                    null,
                    $ipAddress
                );
            }

            Log::info('Device online', ['device' => $serialNumber]);
        }
    }

    /**
     * Get a device by serial number.
     */
    public function getDevice(string $serialNumber): ?ZktecoDevice
    {
        $modelClass = config('zkteco-adms.models.device', ZktecoDevice::class);

        return $modelClass::where('serial_number', $serialNumber)->first();
    }

    /**
     * Check if a device exists by serial number.
     */
    public function deviceExists(string $serialNumber): bool
    {
        $modelClass = config('zkteco-adms.models.device', ZktecoDevice::class);

        return $modelClass::where('serial_number', $serialNumber)->exists();
    }

    /**
     * Set the timezone for a device.
     */
    public function setDeviceTimezone(string $serialNumber, string $timezone): void
    {
        $device = $this->getDevice($serialNumber);
        if (! $device instanceof ZktecoDevice) {
            throw new DeviceNotFoundException($serialNumber);
        }
        $device->update(['timezone' => $timezone]);
    }

    /**
     * Get the timezone for a device.
     */
    public function getDeviceTimezone(string $serialNumber): string
    {
        $device = $this->getDevice($serialNumber);
        if ($device instanceof ZktecoDevice) {
            return $device->getEffectiveTimezone();
        }

        return config('zkteco-adms.default_timezone', 'UTC');
    }

    /**
     * Merge new options into a device's options field.
     *
     * @param  array<string, mixed>  $options
     */
    public function updateDeviceOptions(string $serialNumber, array $options): void
    {
        $device = $this->getDevice($serialNumber);
        if (! $device instanceof ZktecoDevice) {
            return;
        }
        $currentOptions = $device->options ?? [];
        $device->update(['options' => array_merge($currentOptions, $options)]);
    }

    /**
     * Update device info fields from a parsed device info payload.
     *
     * @param  array<string, string>  $info
     */
    public function updateDeviceInfo(string $serialNumber, array $info): void
    {
        $device = $this->getDevice($serialNumber);
        if (! $device instanceof ZktecoDevice) {
            return;
        }

        $updates = [];
        if (isset($info['FWVersion'])) {
            $updates['firmware_version'] = $info['FWVersion'];
        }
        if (isset($info['DeviceName'])) {
            $updates['name'] = $info['DeviceName'];
        }
        if (isset($info['Platform'])) {
            $updates['device_type'] = $info['Platform'];
        }
        if (isset($info['PushVersion'])) {
            $updates['push_version'] = $info['PushVersion'];
        }

        if (! empty($updates)) {
            $device->update($updates);
        }

        $this->updateDeviceOptions($serialNumber, $info);

        if (config('zkteco-adms.events.dispatch_device_event', true)) {
            ZktecoDeviceEvent::record(
                $device->id,
                DeviceEventType::InfoReceived,
                $info,
                request()->ip()
            );
        }
    }

    /**
     * List all registered devices.
     *
     * @return Collection<int, ZktecoDevice>
     */
    public function listDevices(): Collection
    {
        $modelClass = config('zkteco-adms.models.device', ZktecoDevice::class);

        return $modelClass::query()->get();
    }

    /**
     * Evict stale devices that have not communicated within the timeout period.
     */
    public function evictStaleDevices(): int
    {
        $timeout = config('zkteco-adms.device_eviction_timeout', 86400);
        $cutoff = Carbon::now()->subSeconds($timeout);
        $modelClass = config('zkteco-adms.models.device', ZktecoDevice::class);

        $stale = $modelClass::where('last_activity_at', '<', $cutoff)
            ->orWhereNull('last_activity_at')
            ->get();

        $count = $stale->count();

        if ($count > 0) {
            foreach ($stale as $device) {
                $device->markAsOffline();
            }

            Log::info('Evicted stale devices', ['count' => $count]);
        }

        return $count;
    }

    /**
     * Get a snapshot of all devices for the inspect endpoint.
     *
     * @return DeviceSnapshot[]
     */
    public function getDeviceSnapshots(): array
    {
        $devices = $this->listDevices();
        $snapshots = [];

        foreach ($devices as $device) {
            $snapshots[] = new DeviceSnapshot(
                serial: $device->serial_number,
                lastActivity: $device->last_activity_at?->toIso8601String() ?? '',
                online: $device->isOnline(),
                options: $device->options ?? [],
                timezone: $device->getEffectiveTimezone(),
                pendingCommands: $device->pendingCommands()->count(),
            );
        }

        return $snapshots;
    }
}
