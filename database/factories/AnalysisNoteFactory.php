<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnalysisType;
use App\Enums\Ethics;
use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Models\AnalysisNote;
use App\Models\SubmissionAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalysisNote>
 */
final class AnalysisNoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_analysis_id' => SubmissionAnalysis::factory(),
            'annotation_id' => 'a'.fake()->unique()->numberBetween(1, 9999),
            'label' => fake()->sentence(),
            'aspect_label' => 'Ad Hominem',
            'text' => fake()->paragraph(),
            'ethics' => Ethics::Questionable,
            'strength' => fake()->numberBetween(1, 10),
        ];
    }

    public function question(QuestionKind $kind = QuestionKind::Factual): self
    {
        return $this->state(fn (array $attributes): array => [
            'submission_analysis_id' => SubmissionAnalysis::factory()->state(['type' => AnalysisType::Questions]),
            'aspect_label' => 'Ի՞նչ ապացույցներ կան։',
            'question_kind' => $kind,
            'status' => QuestionStatus::Open,
        ]);
    }
}
