<?php

namespace Athwari\LaravelZktecoAdms\Models;

use Athwari\LaravelZktecoAdms\Database\Factories\ZktecoDeviceEventFactory;
use Athwari\LaravelZktecoAdms\Enums\DeviceEventType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZktecoDeviceEvent extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return ZktecoDeviceEventFactory::new();
    }

    /**
     * Events are immutable — only created_at is used.
     */
    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'event_type' => DeviceEventType::class,
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('zkteco-adms.table_prefix', 'zkteco_').'device_events';
    }

    /** @return BelongsTo<Model, $this> */
    public function device(): BelongsTo
    {
        $model = config('zkteco-adms.models.device', ZktecoDevice::class);

        return $this->belongsTo($model, 'device_id');
    }

    /**
     * Override the creation to set created_at manually since timestamps is disabled.
     */
    public static function record(int $deviceId, DeviceEventType $eventType, ?array $payload = null, ?string $ipAddress = null): self
    {
        $model = new self();

        return $model->query()->create([
            'device_id' => $deviceId,
            'event_type' => $eventType,
            'payload' => $payload,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }
}
