<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SubmissionState;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
final class SubmissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $body = config()->string('analysis.sample_text');

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'body' => $body,
            'source' => null,
            'normalized_url' => null,
            'url_hash' => null,
            'content_hash' => hash('sha256', mb_strtolower($body)),
            'state' => SubmissionState::Pending,
        ];
    }

    public function analyzed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'state' => SubmissionState::Analyzed,
        ]);
    }

    public function processing(): self
    {
        return $this->state(fn (array $attributes): array => [
            'state' => SubmissionState::Processing,
        ]);
    }
}
