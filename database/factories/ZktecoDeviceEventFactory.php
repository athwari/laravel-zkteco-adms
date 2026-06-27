<?php

namespace Athwari\LaravelZktecoAdms\Database\Factories;

use Athwari\LaravelZktecoAdms\Enums\DeviceEventType;
use Athwari\LaravelZktecoAdms\Models\ZktecoDevice;
use Athwari\LaravelZktecoAdms\Models\ZktecoDeviceEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ZktecoDeviceEvent>
 */
class ZktecoDeviceEventFactory extends Factory
{
    protected $model = ZktecoDeviceEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_id' => ZktecoDevice::factory(),
            'event_type' => $this->faker->randomElement(DeviceEventType::cases()),
            'payload' => null,
            'ip_address' => $this->faker->ipv4(),
            'created_at' => now(),
        ];
    }

    public function connected(): static
    {
        return $this->state(fn () => [
            'event_type' => DeviceEventType::Connected,
        ]);
    }

    public function registered(): static
    {
        return $this->state(fn () => [
            'event_type' => DeviceEventType::Registered,
        ]);
    }
}
