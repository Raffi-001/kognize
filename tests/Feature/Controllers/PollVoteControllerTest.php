<?php

declare(strict_types=1);

use App\Models\Poll;
use App\Models\PollVote;
use App\Models\User;

it('requires authentication', function (): void {
    $poll = Poll::factory()->published()->withOptions()->create();

    $this->post(route('polls.votes.store', $poll), ['poll_option_id' => $poll->options[0]->id])
        ->assertRedirectToRoute('login');
});

it('may answer a published poll', function (?string $reason, ?string $expected): void {
    $user = User::factory()->create();
    $poll = Poll::factory()->published()->withOptions()->create();

    $response = $this->actingAs($user)
        ->fromRoute('polls.index')
        ->post(route('polls.votes.store', $poll), [
            'poll_option_id' => $poll->options[1]->id,
            'reason' => $reason,
        ]);

    $response->assertRedirectToRoute('polls.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Thanks, your answer was recorded.')]);

    $vote = PollVote::query()->sole();

    expect($vote->user->is($user))->toBeTrue()
        ->and($vote->poll->is($poll))->toBeTrue()
        ->and($vote->option->is($poll->options[1]))->toBeTrue()
        ->and($vote->reason)->toBe($expected);
})->with([
    'with a reason' => ['Որովհետև', 'Որովհետև'],
    'without a reason' => [null, null],
]);

it('validates the answer', function (Closure $data, string $error): void {
    $poll = Poll::factory()->published()->withOptions()->create();

    $this->actingAs(User::factory()->create())
        ->fromRoute('polls.index')
        ->post(route('polls.votes.store', $poll), $data($poll))
        ->assertRedirectToRoute('polls.index')
        ->assertSessionHasErrors($error);

    expect(PollVote::query()->count())->toBe(0);
})->with([
    'missing option' => [fn (Poll $poll): array => [], 'poll_option_id'],
    'not an integer' => [fn (Poll $poll): array => ['poll_option_id' => 'first'], 'poll_option_id'],
    'option of another poll' => [fn (Poll $poll): array => ['poll_option_id' => Poll::factory()->published()->withOptions()->create()->options[0]->id], 'poll_option_id'],
    'long reason' => [fn (Poll $poll): array => ['poll_option_id' => $poll->options[0]->id, 'reason' => str_repeat('a', 501)], 'reason'],
]);

it('only accepts answers while the poll is published', function (Closure $poll): void {
    $poll = $poll();

    $this->actingAs(User::factory()->create())
        ->fromRoute('polls.index')
        ->post(route('polls.votes.store', $poll), ['poll_option_id' => $poll->options[0]->id])
        ->assertRedirectToRoute('polls.index')
        ->assertSessionHasErrors(['poll_option_id' => 'This question is no longer open for answers.']);

    expect(PollVote::query()->count())->toBe(0);
})->with([
    'draft' => [fn () => Poll::factory()->withOptions()->create()],
    'closed' => [fn () => Poll::factory()->closed()->withOptions()->create()],
]);
