<?php

declare(strict_types=1);

use App\Enums\EvidenceAssessment;
use App\Enums\EvidenceVerification;
use App\Enums\Stance;
use App\Models\AnalysisNote;
use App\Models\Evidence;
use App\Models\User;

test('to array', function (): void {
    $evidence = Evidence::factory()->create()->refresh();

    expect(array_keys($evidence->toArray()))
        ->toBe([
            'id',
            'analysis_note_id',
            'user_id',
            'stance',
            'url',
            'quote',
            'explanation',
            'verification',
            'page_title',
            'snapshot',
            'assessment',
            'assessment_reason',
            'created_at',
            'updated_at',
            'score',
        ]);
});

it('defaults to pending verification without an assessment', function (): void {
    $evidence = new Evidence;

    expect($evidence->verification)->toBe(EvidenceVerification::Pending)
        ->and($evidence->assessment)->toBeNull()
        ->and($evidence->snapshot)->toBeNull();
});

it('casts its attributes', function (): void {
    $evidence = Evidence::factory()->verified(EvidenceAssessment::Refutes)->create(['stance' => Stance::Refutes])->refresh();

    expect($evidence->stance)->toBe(Stance::Refutes)
        ->and($evidence->verification)->toBe(EvidenceVerification::Verified)
        ->and($evidence->assessment)->toBe(EvidenceAssessment::Refutes);
});

it('belongs to a question and a user', function (): void {
    $question = AnalysisNote::factory()->question()->create();
    $user = User::factory()->create();
    $evidence = Evidence::factory()->for($question, 'question')->for($user)->create();

    expect($evidence->question->is($question))->toBeTrue()
        ->and($evidence->user->is($user))->toBeTrue();
});
