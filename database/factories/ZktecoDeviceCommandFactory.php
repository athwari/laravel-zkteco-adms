<?php

namespace Athwari\LaravelZktecoAdms\Database\Factories;

use Athwari\LaravelZktecoAdms\Enums\CommandStatus;
use Athwari\LaravelZktecoAdms\Enums\CommandType;
use Athwari\LaravelZktecoAdms\Models\ZktecoDevice;
use Athwari\LaravelZktecoAdms\Models\ZktecoDeviceCommand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ZktecoDeviceCommand>
 */
class ZktecoDeviceCommandFactory extends Factory
{
    protected $model = ZktecoDeviceCommand::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_id' => ZktecoDevice::factory(),
            'command_id' => null,
            'command_type' => $this->faker->randomElement(CommandType::cases()),
            'command_content' => 'INFO',
            'status' => CommandStatus::Pending,
            'return_code' => null,
            'queued_at' => now(),
            'sent_at' => null,
            'acknowledged_at' => null,
            'response' => null,
            'retry_count' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => CommandStatus::Pending,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => CommandStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function acknowledged(): static
    {
        return $this->state(fn () => [
            'status' => CommandStatus::Acknowledged,
            'sent_at' => now()->subMinutes(5),
            'acknowledged_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => CommandStatus::Failed,
            'sent_at' => now()->subMinutes(5),
            'response' => 'Device timeout',
        ]);
    }
}
