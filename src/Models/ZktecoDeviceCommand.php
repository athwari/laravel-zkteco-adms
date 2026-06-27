<?php

namespace Athwari\LaravelZktecoAdms\Models;

use Athwari\LaravelZktecoAdms\Database\Factories\ZktecoDeviceCommandFactory;
use Athwari\LaravelZktecoAdms\Enums\CommandStatus;
use Athwari\LaravelZktecoAdms\Enums\CommandType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $retry_count
 * @property CommandStatus $status
 */
class ZktecoDeviceCommand extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return ZktecoDeviceCommandFactory::new();
    }

    /**
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => CommandStatus::class,
        'command_type' => CommandType::class,
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('zkteco-adms.table_prefix', 'zkteco_').'device_commands';
    }

    /** @return BelongsTo<Model, $this> */
    public function device(): BelongsTo
    {
        $model = config('zkteco-adms.models.device', ZktecoDevice::class);

        return $this->belongsTo($model, 'device_id');
    }

    /**
     * Mark the command as sent to the device.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => CommandStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark the command as acknowledged by the device.
     */
    public function markAsAcknowledged(?string $response = null, ?int $returnCode = null): void
    {
        $this->update([
            'status' => CommandStatus::Acknowledged,
            'acknowledged_at' => now(),
            'response' => $response,
            'return_code' => $returnCode,
        ]);
    }

    /**
     * Mark the command as failed.
     */
    public function markAsFailed(?string $response = null): void
    {
        $this->update([
            'status' => CommandStatus::Failed,
            'response' => $response,
        ]);
    }

    /**
     * Retry a failed or sent command by resetting its status.
     */
    public function retry(): void
    {
        $this->update([
            'status' => CommandStatus::Pending,
            'sent_at' => null,
            'acknowledged_at' => null,
            'response' => null,
            'return_code' => null,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Determine if the command has been confirmed by the device.
     */
    public function isConfirmed(): bool
    {
        return $this->status === CommandStatus::Acknowledged;
    }
}
