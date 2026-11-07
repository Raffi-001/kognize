<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AnalysisStatus;
use App\Enums\SubmissionState;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;

final readonly class FinalizeSubmissionAnalysis
{
    public function handle(Submission $submission): Submission
    {
        $completed = $submission->analyses()
            ->get()
            ->contains(fn (SubmissionAnalysis $analysis): bool => $analysis->status === AnalysisStatus::Completed);

        $submission->update([
            'state' => $completed ? SubmissionState::Analyzed : SubmissionState::Failed,
        ]);

        return $submission;
    }
}
