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
