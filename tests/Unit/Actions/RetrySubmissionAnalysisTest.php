<?php

declare(strict_types=1);

use App\Actions\RetrySubmissionAnalysis;
use App\Enums\SubmissionKind;
use App\Enums\SubmissionState;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('analyzes a failed submission again from scratch', function (): void {
    Bus::fake();

    $submission = Submission::factory()->failed()->create(['kind' => SubmissionKind::Argument]);
    SubmissionAnalysis::factory()->for($submission)->create();

    resolve(RetrySubmissionAnalysis::class)->handle($submission);

    expect($submission->refresh())
        ->state->toBe(SubmissionState::Pending)
        ->kind->toBeNull()
        ->and($submission->analyses()->count())->toBe(0);

    Bus::assertBatched(fn (PendingBatch $batch): bool => $batch->name === 'Analyze submission #'.$submission->id);
});

it('refuses to retry a submission that did not fail', function (): void {
    Bus::fake();

    $submission = Submission::factory()->analyzed()->create();
    SubmissionAnalysis::factory()->for($submission)->create();

    expect(fn () => resolve(RetrySubmissionAnalysis::class)->handle($submission))
        ->toThrow(HttpException::class, 'Only failed submissions can be retried.');

    expect($submission->analyses()->count())->toBe(1);

    Bus::assertNothingBatched();
});
