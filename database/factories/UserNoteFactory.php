<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Submission;
use App\Models\User;
use App\Models\UserNote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserNote>
 */
final class UserNoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'user_id' => User::factory(),
            'annotation_id' => 'un-'.Str::lower(Str::random(12)),
            'label' => null,
            'text' => fake()->paragraph(),
        ];
    }
}
