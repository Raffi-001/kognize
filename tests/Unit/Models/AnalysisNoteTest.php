<?php

declare(strict_types=1);

use App\Enums\Ethics;
use App\Enums\EvidenceAssessment;
use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Enums\Verdict;
use App\Models\AnalysisNote;
use App\Models\Comment;
use App\Models\Evidence;
use App\Models\Poll;
use App\Models\SubmissionAnalysis;
use App\Models\User;

test('to array', function (): void {
    $note = AnalysisNote::factory()->create()->refresh();

    expect(array_keys($note->toArray()))
        ->toBe([
            'id',
            'submission_analysis_id',
            'annotation_id',
            'label',
            'aspect_label',
            'text',
            'ethics',
            'strength',
            'created_at',
            'updated_at',
            'question_kind',
            'status',
            'verdict',
            'resolution',
            'resolved_by',
            'resolved_at',
        ]);
});

it('defaults to a neutral, medium strength note that is not a question', function (): void {
    $note = new AnalysisNote;

    expect($note->ethics)->toBe(Ethics::Neutral)
        ->and($note->strength)->toBe(5)
        ->and($note->question_kind)->toBeNull()
        ->and($note->status)->toBeNull()
        ->and($note->verdict)->toBeNull();
});

it('casts its question fields', function (): void {
    $note = AnalysisNote::factory()->question(QuestionKind::Value)->create([
        'verdict' => Verdict::Contested,
        'resolved_at' => now(),
    ])->refresh();

    expect($note->question_kind)->toBe(QuestionKind::Value)
        ->and($note->status)->toBe(QuestionStatus::Open)
        ->and($note->verdict)->toBe(Verdict::Contested)
        ->and($note->resolved_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});

it('belongs to an analysis and has comments', function (): void {
    $analysis = SubmissionAnalysis::factory()->create();
    $note = AnalysisNote::factory()->for($analysis, 'analysis')->create();
    $comment = Comment::factory()->for($note, 'commentable')->create();

    expect($note->analysis->is($analysis))->toBeTrue()
        ->and($note->comments->sole()->is($comment))->toBeTrue();
});

it('has the latest evidence first, a resolver and its poll without the mirror', function (): void {
    $moderator = User::factory()->admin()->create();
    $note = AnalysisNote::factory()->question()->create(['resolved_by' => $moderator->id]);
    $older = Evidence::factory()->for($note, 'question')->create();
    $newer = Evidence::factory()->for($note, 'question')->create();
    $poll = Poll::factory()->create(['analysis_note_id' => $note->id]);
    Poll::factory()->create(['analysis_note_id' => $note->id, 'mirror_of_id' => $poll->id]);

    expect($note->evidence->pluck('id')->all())->toBe([$newer->id, $older->id])
        ->and($note->resolver->is($moderator))->toBeTrue()
        ->and($note->poll->is($poll))->toBeTrue();
});

it('suggests the verdict the verified evidence points to', function (array $evidence, ?Verdict $verdict): void {
    $note = AnalysisNote::factory()->question()->create();

    foreach ($evidence as $factory) {
        $factory->for($note, 'question')->create();
    }

    expect($note->suggestedVerdict())->toBe($verdict);
})->with([
    'no evidence' => [fn (): array => [], null],
    'unverified evidence' => [fn (): array => [Evidence::factory(), Evidence::factory()->verified()->state(['verification' => 'not_found'])], null],
    'irrelevant evidence' => [fn (): array => [Evidence::factory()->verified(EvidenceAssessment::Irrelevant)], null],
    'supporting evidence' => [fn (): array => [Evidence::factory()->verified(EvidenceAssessment::Supports)], Verdict::Supported],
    'refuting evidence' => [fn (): array => [Evidence::factory()->verified(EvidenceAssessment::Refutes)], Verdict::Refuted],
    'both' => [fn (): array => [Evidence::factory()->verified(EvidenceAssessment::Supports), Evidence::factory()->verified(EvidenceAssessment::Refutes)], Verdict::Contested],
]);
