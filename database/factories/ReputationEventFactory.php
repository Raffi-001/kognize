<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReputationReason;
use App\Models\ReputationEvent;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReputationEvent>
 */
final class ReputationEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vote_id' => Vote::factory(),
            'reason' => ReputationReason::NoteUpvoted,
            'amount' => 10,
        ];
    }
}
