<?php

declare(strict_types=1);

use App\Actions\DispatchSubmissionAnalysis;
use App\Enums\AnalysisStatus;
use App\Enums\SubmissionKind;
use App\Enums\SubmissionState;
use App\Jobs\MapSubmissionJob;
use App\Models\Submission;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;

it('dispatches a batch with one argument map job', function (): void {
    Bus::fake();

    $submission = Submission::factory()->create();

    resolve(DispatchSubmissionAnalysis::class)->handle($submission);

    Bus::assertBatched(fn (PendingBatch $batch): bool => $batch->name === 'Analyze submission #'.$submission->id
        && $batch->allowsFailures()
        && $batch->jobs->count() === 1
        && $batch->jobs->first() instanceof MapSubmissionJob
        && $batch->jobs->first()->submission->is($submission));
});

it('finalizes the submission when the batch finishes', function (): void {
    Bus::fake();

    $submission = Submission::factory()->processing()->create();

    resolve(DispatchSubmissionAnalysis::class)->handle($submission);

    Bus::assertBatched(function (PendingBatch $batch): bool {
        expect($batch->finallyCallbacks())->toHaveCount(1);

        $batch->finallyCallbacks()[0]->getClosure()();

        return true;
    });

    expect($submission->refresh()->state)->toBe(SubmissionState::Failed);
});

it('marks the submission as analyzed when the batch finishes', function (): void {
    fakeOpenAi(['text_kind' => 'argument', 'claims' => [], 'techniques' => []]);

    $submission = Submission::factory()->create();

    resolve(DispatchSubmissionAnalysis::class)->handle($submission);

    expect($submission->refresh()->state)->toBe(SubmissionState::Analyzed)
        ->and($submission->kind)->toBe(SubmissionKind::Argument)
        ->and($submission->analyses)->toHaveCount(3)
        ->and($submission->analyses->every(fn ($analysis): bool => $analysis->status === AnalysisStatus::Completed))->toBeTrue();
});

it('marks the submission as failed when the analysis fails', function (): void {
    Exceptions::fake();

    Http::fake(['*' => Http::response('Error', 500)]);

    config(['services.openai.key' => 'test-key', 'services.openai.url' => 'https://api.openai.com/v1']);

    $submission = Submission::factory()->create();

    resolve(DispatchSubmissionAnalysis::class)->handle($submission);

    expect($submission->refresh()->state)->toBe(SubmissionState::Failed);
});
