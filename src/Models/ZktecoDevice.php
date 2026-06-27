<?php

namespace Athwari\LaravelZktecoAdms\Models;

use Athwari\LaravelZktecoAdms\Database\Factories\ZktecoDeviceFactory;
use Athwari\LaravelZktecoAdms\Enums\DeviceStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string|null $serial_number
 * @property CarbonInterface|null $last_activity_at
 * @property array<string, mixed>|null $options
 * @property string|null $timezone
 */
class ZktecoDevice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory(): Factory
    {
        return ZktecoDeviceFactory::new();
    }

    /**
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => DeviceStatus::class,
        'last_activity_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'options' => 'array',
    ];

    public function getTable(): string
    {
        return config('zkteco-adms.table_prefix', 'zkteco_').'devices';
    }

    /** @return HasMany<Model, $this> */
    public function attendanceLogs(): HasMany
    {
        $model = config('zkteco-adms.models.attendance_log', ZktecoAttendanceLog::class);

        return $this->hasMany($model, 'device_id');
    }

    /** @return HasMany<Model, $this> */
    public function deviceCommands(): HasMany
    {
        $model = config('zkteco-adms.models.device_command', ZktecoDeviceCommand::class);

        return $this->hasMany($model, 'device_id');
    }

    /** @return Builder<Model> */
    public function pendingCommands()
    {
        return $this->deviceCommands()->where('status', 'pending');
    }

    /** @return HasMany<Model, $this> */
    public function zktecoUsers(): HasMany
    {
        $model = config('zkteco-adms.models.user', ZktecoUser::class);

        return $this->hasMany($model, 'device_id');
    }

    /** @return HasMany<Model, $this> */
    public function deviceEvents(): HasMany
    {
        $model = config('zkteco-adms.models.device_event', ZktecoDeviceEvent::class);

        return $this->hasMany($model, 'device_id');
    }

    /**
     * Determine if the device is currently online based on last activity.
     */
    public function isOnline(): bool
    {
        if (! $this->last_activity_at) {
            return false;
        }

        $threshold = config('zkteco-adms.device.offline_threshold_minutes', 10);

        return $this->last_activity_at->diffInMinutes(now()) < $threshold;
    }

    /**
     * Update the device status and activity timestamp to indicate it is online.
     */
    public function markAsOnline(): void
    {
        $this->update([
            'status' => DeviceStatus::Online,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Update the device status to indicate it is offline.
     */
    public function markAsOffline(): void
    {
        $this->update([
            'status' => DeviceStatus::Offline,
        ]);
    }

    /**
     * Get the effective timezone for this device, falling back to the global default.
     */
    public function getEffectiveTimezone(): string
    {
        return $this->timezone ?? config('zkteco-adms.default_timezone', 'UTC');
    }
}
