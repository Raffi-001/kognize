<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnalysisStatus;
use App\Enums\AnalysisType;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionAnalysis>
 */
final class SubmissionAnalysisFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'type' => AnalysisType::Fallacy,
            'status' => AnalysisStatus::Completed,
            'result' => null,
        ];
    }
}
