<?php

declare(strict_types=1);

use App\Actions\FinalizeSubmissionAnalysis;
use App\Enums\AnalysisStatus;
use App\Enums\AnalysisType;
use App\Enums\SubmissionState;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;

it('marks the submission as analyzed when any analysis completed', function (): void {
    $submission = Submission::factory()->processing()->create();

    SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Fallacy, 'status' => AnalysisStatus::Failed]);
    SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Rhetoric, 'status' => AnalysisStatus::Completed]);

    $result = resolve(FinalizeSubmissionAnalysis::class)->handle($submission);

    expect($result->state)->toBe(SubmissionState::Analyzed)
        ->and($submission->refresh()->state)->toBe(SubmissionState::Analyzed);
});

it('marks the submission as failed when no analysis completed', function (): void {
    $submission = Submission::factory()->processing()->create();

    SubmissionAnalysis::factory()->for($submission)->create(['status' => AnalysisStatus::Failed]);

    resolve(FinalizeSubmissionAnalysis::class)->handle($submission);

    expect($submission->refresh()->state)->toBe(SubmissionState::Failed);
});

it('marks the submission as failed when there are no analyses', function (): void {
    $submission = Submission::factory()->processing()->create();

    resolve(FinalizeSubmissionAnalysis::class)->handle($submission);

    expect($submission->refresh()->state)->toBe(SubmissionState::Failed);
});

it('tells the author whether the analysis is ready', function (AnalysisStatus $status, string $kind): void {
    $submission = Submission::factory()->processing()->create(['title' => 'Coffee ad']);
    SubmissionAnalysis::factory()->for($submission)->create(['status' => $status]);

    resolve(FinalizeSubmissionAnalysis::class)->handle($submission);

    $notification = $submission->user->notifications()->sole();

    expect($notification->type)->toBe($kind)
        ->and($notification->data['subject'])->toBe(['type' => 'submission', 'id' => $submission->id, 'title' => 'Coffee ad'])
        ->and($notification->data['actors'])->toBe([]);
})->with([
    [AnalysisStatus::Completed, 'analysis_completed'],
    [AnalysisStatus::Failed, 'analysis_failed'],
]);
