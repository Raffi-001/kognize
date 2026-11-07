<?php

declare(strict_types=1);

use App\Actions\CastPollVote;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\User;

it('records an answer with a trimmed reason', function (?string $reason, ?string $expected): void {
    $poll = Poll::factory()->published()->withOptions()->create();
    $user = User::factory()->create();

    $vote = resolve(CastPollVote::class)->handle($poll->options[1], $user, $reason);

    expect($vote->poll->is($poll))->toBeTrue()
        ->and($vote->option->is($poll->options[1]))->toBeTrue()
        ->and($vote->user->is($user))->toBeTrue()
        ->and($vote->reason)->toBe($expected);
})->with([
    'reason' => ['  Որովհետև  ', 'Որովհետև'],
    'blank reason' => ['   ', null],
    'no reason' => [null, null],
]);

it('changes the answer of a user instead of adding another', function (): void {
    $poll = Poll::factory()->published()->withOptions()->create();
    $user = User::factory()->create();

    resolve(CastPollVote::class)->handle($poll->options[0], $user, 'Առաջին');
    $vote = resolve(CastPollVote::class)->handle($poll->options[2], $user);

    expect(PollVote::query()->sole()->is($vote))->toBeTrue()
        ->and($vote->poll_option_id)->toBe($poll->options[2]->id)
        ->and($vote->reason)->toBeNull();
});
