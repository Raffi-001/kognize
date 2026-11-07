<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invitation>
 */
final class InvitationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => (string) fake()->unique()->numberBetween(10000, 99999),
            'email' => fake()->unique()->safeEmail(),
            'inviter_id' => User::factory(),
            'invitee_id' => null,
            'used_at' => null,
        ];
    }

    public function used(): self
    {
        return $this->state(fn (array $attributes): array => [
            'invitee_id' => User::factory(),
            'used_at' => now(),
        ]);
    }
}
