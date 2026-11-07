<?php

declare(strict_types=1);

use App\Enums\AnalysisStatus;
use App\Enums\SubmissionKind;
use App\Jobs\MapSubmissionJob;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;

it('maps the argument of the submission', function (): void {
    fakeOpenAi(['text_kind' => 'personal']);

    $submission = Submission::factory()->create();

    dispatch_sync(new MapSubmissionJob($submission));

    expect($submission->refresh()->kind)->toBe(SubmissionKind::Personal)
        ->and($submission->analyses)->toHaveCount(3)
        ->and($submission->analyses->every(fn (SubmissionAnalysis $analysis): bool => $analysis->status === AnalysisStatus::Completed))->toBeTrue();
});

it('marks every analysis as failed when the job fails', function (): void {
    $submission = Submission::factory()->create();

    new MapSubmissionJob($submission)->failed();

    expect($submission->analyses)->toHaveCount(3)
        ->and($submission->analyses->every(fn (SubmissionAnalysis $analysis): bool => $analysis->status === AnalysisStatus::Failed))->toBeTrue();
});
