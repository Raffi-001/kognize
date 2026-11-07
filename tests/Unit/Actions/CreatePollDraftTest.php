<?php

declare(strict_types=1);

use App\Actions\CreatePollDraft;
use App\Enums\PollStatus;
use App\Enums\QuestionKind;
use App\Models\AnalysisNote;

it('creates a draft poll with its options', function (): void {
    $question = AnalysisNote::factory()->question(QuestionKind::Value)->create();

    $poll = resolve(CreatePollDraft::class)->handle($question, [
        'question' => 'Հարց',
        'options' => ['Այո', 'Ոչ', 'Կախված է'],
        'mirror' => null,
    ]);

    expect($poll->source->is($question))->toBeTrue()
        ->and($poll->question)->toBe('Հարց')
        ->and($poll->status)->toBe(PollStatus::Draft)
        ->and($poll->mirror_of_id)->toBeNull()
        ->and($poll->mirror)->toBeNull()
        ->and($poll->options->pluck('label')->all())->toBe(['Այո', 'Ոչ', 'Կախված է'])
        ->and($poll->options->pluck('position')->all())->toBe([0, 1, 2]);
});

it('creates the mirrored poll as a draft too', function (): void {
    $question = AnalysisNote::factory()->question(QuestionKind::Value)->create();

    $poll = resolve(CreatePollDraft::class)->handle($question, [
        'question' => 'Հարց',
        'options' => ['Այո', 'Ոչ'],
        'mirror' => ['question' => 'Հայելի', 'options' => ['Ա', 'Բ']],
    ]);

    $mirror = $poll->mirror;

    expect($mirror->question)->toBe('Հայելի')
        ->and($mirror->status)->toBe(PollStatus::Draft)
        ->and($mirror->mirrorOf->is($poll))->toBeTrue()
        ->and($mirror->source->is($question))->toBeTrue()
        ->and($mirror->options->pluck('label')->all())->toBe(['Ա', 'Բ'])
        ->and($question->poll->is($poll))->toBeTrue();
});
