<?php

namespace Athwari\LaravelZktecoAdms\Database\Factories;

use Athwari\LaravelZktecoAdms\Enums\DeviceStatus;
use Athwari\LaravelZktecoAdms\Models\ZktecoDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ZktecoDevice>
 */
class ZktecoDeviceFactory extends Factory
{
    protected $model = ZktecoDevice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'serial_number' => strtoupper($this->faker->unique()->bothify('????########')),
            'name' => $this->faker->company().' Device',
            'ip_address' => $this->faker->ipv4(),
            'model' => $this->faker->randomElement(['ZK-F22', 'ZK-K40', 'ZK-iFace302', 'ZK-SpeedFace']),
            'firmware_version' => $this->faker->numerify('#.#.#'),
            'push_version' => '2.4.1',
            'device_type' => 'attendance',
            'status' => DeviceStatus::Unknown,
            'last_activity_at' => null,
            'last_sync_at' => null,
            'att_stamp' => 0,
            'op_stamp' => 0,
            'options' => null,
            'timezone' => null,
        ];
    }

    public function online(): static
    {
        return $this->state(fn () => [
            'status' => DeviceStatus::Online,
            'last_activity_at' => now(),
            'last_sync_at' => now(),
        ]);
    }

    public function offline(): static
    {
        return $this->state(fn () => [
            'status' => DeviceStatus::Offline,
            'last_activity_at' => now()->subHours(2),
        ]);
    }
}
