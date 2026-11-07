<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UrlSubmissionStatus;
use App\Models\UrlSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UrlSubmission>
 */
final class UrlSubmissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'submission_id' => null,
            'url' => fake()->url(),
            'status' => UrlSubmissionStatus::Pending,
            'error_message' => null,
        ];
    }
}
