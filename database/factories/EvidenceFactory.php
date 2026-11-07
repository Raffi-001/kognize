<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EvidenceAssessment;
use App\Enums\EvidenceVerification;
use App\Enums\Stance;
use App\Models\AnalysisNote;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evidence>
 */
final class EvidenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'analysis_note_id' => AnalysisNote::factory()->question(),
            'user_id' => User::factory(),
            'stance' => Stance::Supports,
            'url' => fake()->url(),
            'quote' => fake()->sentence(),
            'explanation' => null,
            'verification' => EvidenceVerification::Pending,
        ];
    }

    public function verified(EvidenceAssessment $assessment = EvidenceAssessment::Supports): self
    {
        return $this->state(fn (array $attributes): array => [
            'verification' => EvidenceVerification::Verified,
            'page_title' => fake()->sentence(3),
            'snapshot' => fake()->paragraph(),
            'assessment' => $assessment,
            'assessment_reason' => fake()->sentence(),
        ]);
    }
}
