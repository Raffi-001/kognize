<?php

declare(strict_types=1);

use App\Actions\MarkSubmissionAnalysisFailed;
use App\Enums\AnalysisStatus;
use App\Enums\AnalysisType;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;

it('marks every analysis type as failed', function (): void {
    $submission = Submission::factory()->create();
    $existing = SubmissionAnalysis::factory()->for($submission)->create([
        'type' => AnalysisType::Fallacy,
        'status' => AnalysisStatus::Running,
    ]);

    resolve(MarkSubmissionAnalysisFailed::class)->handle($submission);

    $analyses = $submission->analyses()->orderBy('id')->get();

    expect($analyses)->toHaveCount(3)
        ->and($analyses[0]->is($existing))->toBeTrue()
        ->and($analyses->map(fn (SubmissionAnalysis $analysis): AnalysisType => $analysis->type)->all())->toBe(AnalysisType::cases())
        ->and($analyses->every(fn (SubmissionAnalysis $analysis): bool => $analysis->status === AnalysisStatus::Failed))->toBeTrue();
});
