<?php

declare(strict_types=1);

use App\Enums\PollStatus;
use App\Models\AnalysisNote;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;

test('to array', function (): void {
    $poll = Poll::factory()->create()->refresh();

    expect(array_keys($poll->toArray()))
        ->toBe([
            'id',
            'analysis_note_id',
            'mirror_of_id',
            'question',
            'status',
            'approved_by',
            'published_at',
            'closed_at',
            'created_at',
            'updated_at',
        ]);
});

it('defaults to a draft', function (): void {
    expect(new Poll()->status)->toBe(PollStatus::Draft);
});

it('casts its attributes', function (): void {
    $poll = Poll::factory()->closed()->create()->refresh();

    expect($poll->status)->toBe(PollStatus::Closed)
        ->and($poll->published_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($poll->closed_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});

it('has a source question, a mirror, ordered options and votes', function (): void {
    $question = AnalysisNote::factory()->question()->create();
    $poll = Poll::factory()->create(['analysis_note_id' => $question->id]);
    $mirror = Poll::factory()->create(['mirror_of_id' => $poll->id]);
    $second = PollOption::factory()->for($poll)->create(['position' => 1]);
    $first = PollOption::factory()->for($poll)->create(['position' => 0]);
    $vote = PollVote::factory()->create(['poll_option_id' => $first->id]);

    expect($poll->source->is($question))->toBeTrue()
        ->and($poll->mirror->is($mirror))->toBeTrue()
        ->and($mirror->mirrorOf->is($poll))->toBeTrue()
        ->and($poll->mirrorOf)->toBeNull()
        ->and($poll->options->pluck('id')->all())->toBe([$first->id, $second->id])
        ->and($poll->votes->sole()->is($vote))->toBeTrue();
});
