<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AnalysisStatus;
use App\Enums\AnalysisType;
use App\Models\Submission;

final readonly class MarkSubmissionAnalysisFailed
{
    public function handle(Submission $submission): void
    {
        foreach (AnalysisType::cases() as $type) {
            $submission->analyses()->updateOrCreate(
                ['type' => $type],
                ['status' => AnalysisStatus::Failed],
            );
        }
    }
}
