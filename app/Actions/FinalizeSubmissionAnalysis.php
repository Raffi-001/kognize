<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AnalysisStatus;
use App\Enums\NotificationKind;
use App\Enums\SubmissionState;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;

final readonly class FinalizeSubmissionAnalysis
{
    public function __construct(private NotifyActivity $notify) {}

    public function handle(Submission $submission): Submission
    {
        $completed = $submission->analyses()
            ->get()
            ->contains(fn (SubmissionAnalysis $analysis): bool => $analysis->status === AnalysisStatus::Completed);

        $submission->update([
            'state' => $completed ? SubmissionState::Analyzed : SubmissionState::Failed,
        ]);

        $this->notify->handle(
            [$submission->user],
            $completed ? NotificationKind::AnalysisCompleted : NotificationKind::AnalysisFailed,
            null,
            ['type' => 'submission', 'id' => $submission->id, 'title' => $submission->displayTitle()],
            route('submissions.show', $submission),
        );

        return $submission;
    }
}
