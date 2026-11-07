<?php

declare(strict_types=1);

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;

test('to array', function (): void {
    $option = PollOption::factory()->create()->refresh();

    expect(array_keys($option->toArray()))
        ->toBe([
            'id',
            'poll_id',
            'label',
            'position',
            'created_at',
            'updated_at',
        ]);
});

it('defaults to the first position', function (): void {
    expect(new PollOption()->position)->toBe(0);
});

it('belongs to a poll and has votes', function (): void {
    $poll = Poll::factory()->create();
    $option = PollOption::factory()->for($poll)->create();
    $vote = PollVote::factory()->create(['poll_option_id' => $option->id]);

    expect($option->poll->is($poll))->toBeTrue()
        ->and($option->votes->sole()->is($vote))->toBeTrue();
});
