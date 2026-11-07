<?php

declare(strict_types=1);

use App\Enums\AnalysisStatus;
use App\Enums\AnalysisType;
use App\Models\AnalysisNote;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;

test('to array', function (): void {
    $analysis = SubmissionAnalysis::factory()->create()->refresh();

    expect(array_keys($analysis->toArray()))
        ->toBe([
            'id',
            'submission_id',
            'type',
            'status',
            'result',
            'created_at',
            'updated_at',
        ]);
});

it('casts its attributes', function (): void {
    $analysis = SubmissionAnalysis::factory()->create([
        'type' => AnalysisType::Rhetoric,
        'status' => AnalysisStatus::Running,
        'result' => ['comments' => []],
    ])->refresh();

    expect($analysis->type)->toBe(AnalysisType::Rhetoric)
        ->and($analysis->status)->toBe(AnalysisStatus::Running)
        ->and($analysis->result)->toBe(['comments' => []]);
});

it('defaults to not started', function (): void {
    expect(new SubmissionAnalysis()->status)->toBe(AnalysisStatus::NotStarted);
});

it('belongs to a submission and has notes', function (): void {
    $submission = Submission::factory()->create();
    $analysis = SubmissionAnalysis::factory()->for($submission)->create();
    $note = AnalysisNote::factory()->for($analysis, 'analysis')->create();

    expect($analysis->submission->is($submission))->toBeTrue()
        ->and($analysis->notes->sole()->is($note))->toBeTrue();
});
