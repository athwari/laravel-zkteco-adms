<?php

namespace Athwari\LaravelZktecoAdms\Database\Factories;

use Athwari\LaravelZktecoAdms\Enums\UserPrivilege;
use Athwari\LaravelZktecoAdms\Models\ZktecoUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ZktecoUser>
 */
class ZktecoUserFactory extends Factory
{
    protected $model = ZktecoUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pin' => (string) $this->faker->unique()->numberBetween(1, 99999),
            'name' => $this->faker->name(),
            'card_number' => $this->faker->optional(0.5)->numerify('##########'),
            'privilege' => UserPrivilege::User,
            'password' => null,
            'group' => '1',
            'is_enabled' => true,
            'fingerprints' => null,
            'face_templates' => null,
            'app_user_id' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'privilege' => UserPrivilege::Admin,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => [
            'is_enabled' => false,
        ]);
    }
}
