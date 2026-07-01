<?php

namespace Athwari\LaravelZktecoAdms\Database\Factories;

use Athwari\LaravelZktecoAdms\Enums\AttendanceStatus;
use Athwari\LaravelZktecoAdms\Enums\VerifyMode;
use Athwari\LaravelZktecoAdms\Models\ZktecoAttendanceLog;
use Athwari\LaravelZktecoAdms\Models\ZktecoDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ZktecoAttendanceLog>
 */
class ZktecoAttendanceLogFactory extends Factory
{
    protected $model = ZktecoAttendanceLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $recordedAt = $this->faker->dateTimeBetween('-30 days');
        $occurredAt = (clone $recordedAt)->setTimezone(new \DateTimeZone(
            config('zkteco-adms.storage_timezone', 'UTC')
        ));

        return [
            'device_id' => ZktecoDevice::factory(),
            'pin' => (string) $this->faker->numberBetween(1, 9999),
            'recorded_at' => $recordedAt,
            'occurred_at' => $occurredAt,
            'status' => $this->faker->randomElement(AttendanceStatus::cases()),
            'verify_mode' => $this->faker->randomElement([VerifyMode::Fingerprint, VerifyMode::Face, VerifyMode::Card]),
            'work_code' => '',
            'reserved_1' => null,
            'reserved_2' => null,
            'raw_data' => null,
        ];
    }

    public function checkIn(): static
    {
        return $this->state(fn () => [
            'status' => AttendanceStatus::CheckIn,
        ]);
    }

    public function checkOut(): static
    {
        return $this->state(fn () => [
            'status' => AttendanceStatus::CheckOut,
        ]);
    }
}
