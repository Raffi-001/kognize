<?php

declare(strict_types=1);

use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\User;

test('to array', function (): void {
    $vote = PollVote::factory()->create()->refresh();

    expect(array_keys($vote->toArray()))
        ->toBe([
            'id',
            'poll_id',
            'poll_option_id',
            'user_id',
            'reason',
            'created_at',
            'updated_at',
        ]);
});

it('defaults to no reason', function (): void {
    expect(new PollVote()->reason)->toBeNull();
});

it('belongs to a poll, an option and a user', function (): void {
    $option = PollOption::factory()->create();
    $user = User::factory()->create();
    $vote = PollVote::factory()->for($user)->create(['poll_option_id' => $option->id]);

    expect($vote->poll->is($option->poll))->toBeTrue()
        ->and($vote->option->is($option))->toBeTrue()
        ->and($vote->user->is($user))->toBeTrue();
});
