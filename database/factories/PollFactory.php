<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PollStatus;
use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Poll>
 */
final class PollFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'analysis_note_id' => null,
            'question' => fake()->sentence().'?',
            'status' => PollStatus::Draft,
        ];
    }

    public function published(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PollStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function closed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PollStatus::Closed,
            'published_at' => now(),
            'closed_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $labels
     */
    public function withOptions(array $labels = ['Այո', 'Ոչ', 'Կախված է']): self
    {
        return $this->afterCreating(function (Poll $poll) use ($labels): void {
            foreach ($labels as $position => $label) {
                PollOption::factory()->for($poll)->create(['label' => $label, 'position' => $position]);
            }
        });
    }
}
