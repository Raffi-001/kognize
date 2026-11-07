<?php

declare(strict_types=1);

use App\Enums\SubmissionKind;
use App\Enums\SubmissionState;
use App\Models\Report;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use App\Models\User;
use App\Models\UserNote;

test('to array', function (): void {
    $submission = Submission::factory()->create()->refresh();

    expect(array_keys($submission->toArray()))
        ->toBe([
            'id',
            'user_id',
            'title',
            'body',
            'source',
            'normalized_url',
            'url_hash',
            'content_hash',
            'state',
            'created_at',
            'updated_at',
            'kind',
            'argument_map',
        ]);
});

it('defaults to the pending state without a kind or argument map', function (): void {
    $submission = new Submission;

    expect($submission->state)->toBe(SubmissionState::Pending)
        ->and($submission->kind)->toBeNull()
        ->and($submission->argument_map)->toBeNull();
});

it('casts its kind and argument map', function (): void {
    $submission = Submission::factory()->create([
        'kind' => SubmissionKind::Informational,
        'argument_map' => ['text_kind' => 'informational', 'claims' => []],
    ])->refresh();

    expect($submission->kind)->toBe(SubmissionKind::Informational)
        ->and($submission->argument_map)->toBe(['text_kind' => 'informational', 'claims' => []]);
});

it('belongs to a user', function (): void {
    $user = User::factory()->create();
    $submission = Submission::factory()->for($user)->create();

    expect($submission->user->is($user))->toBeTrue();
});

it('has analyses, user notes and reports', function (): void {
    $submission = Submission::factory()->create();
    $analysis = SubmissionAnalysis::factory()->for($submission)->create();
    $note = UserNote::factory()->for($submission)->create();
    $report = Report::factory()->create(['reportable_id' => $submission->id]);

    expect($submission->analyses->sole()->is($analysis))->toBeTrue()
        ->and($submission->userNotes->sole()->is($note))->toBeTrue()
        ->and($submission->reports->sole()->is($report))->toBeTrue();
});
