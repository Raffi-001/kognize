<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AnalysisNote;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
final class CommentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commentable_type' => 'analysis_note',
            'commentable_id' => AnalysisNote::factory(),
            'user_id' => User::factory(),
            'text' => fake()->sentence(),
        ];
    }
}
