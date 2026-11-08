<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Enums\ReputationReason;
use App\Models\Comment;
use App\Models\Evidence;
use App\Models\ReputationEvent;
use App\Models\User;
use App\Models\UserNote;
use App\Models\Vote;
use Illuminate\Validation\ValidationException;

function castVote(User $voter, UserNote|Comment|Evidence $votable, int $value): ?Vote
{
    return resolve(CastVote::class)->handle($voter, $votable, $value);
}

it('rewards the author of upvoted content', function (Closure $votable, int $points, ReputationReason $reason): void {
    $content = $votable();
    $voter = User::factory()->create();

    $vote = castVote($voter, $content, 1);

    expect($vote?->value)->toBe(1)
        ->and($content->refresh()->score)->toBe(1)
        ->and($content->user->refresh()->reputation)->toBe(1 + $points)
        ->and($content->user->reputationEvents()->sole()->reason)->toBe($reason)
        ->and($voter->refresh()->reputation)->toBe(1);
})->with([
    'community note' => [fn () => UserNote::factory()->create(), 10, ReputationReason::NoteUpvoted],
    'source' => [fn () => Evidence::factory()->create(), 10, ReputationReason::EvidenceUpvoted],
    'reply' => [fn () => Comment::factory()->create(), 2, ReputationReason::CommentUpvoted],
]);

it('costs the author, and the voter on notes and sources, when downvoting', function (Closure $votable, int $authorLoss, int $voterLoss): void {
    $content = $votable();
    $content->user->update(['reputation' => 100]);
    ReputationEvent::factory()->for($content->user)->create(['amount' => 99]);
    $voter = User::factory()->create(['reputation' => 60]);
    ReputationEvent::factory()->for($voter)->create(['amount' => 59]);

    castVote($voter, $content, -1);

    expect($content->refresh()->score)->toBe(-1)
        ->and($content->user->refresh()->reputation)->toBe(100 - $authorLoss)
        ->and($voter->refresh()->reputation)->toBe(60 - $voterLoss);
})->with([
    'community note' => [fn () => UserNote::factory()->create(), 2, 1],
    'source' => [fn () => Evidence::factory()->create(), 2, 1],
    'reply' => [fn () => Comment::factory()->create(), 1, 0],
]);

it('takes a vote back when it is cast again', function (): void {
    $note = UserNote::factory()->create();
    $voter = User::factory()->create();

    castVote($voter, $note, 1);
    $result = castVote($voter, $note, 1);

    expect($result)->toBeNull()
        ->and(Vote::query()->count())->toBe(0)
        ->and($note->refresh()->score)->toBe(0)
        ->and($note->user->refresh()->reputation)->toBe(1);
});

it('switches an upvote to a downvote', function (): void {
    $note = UserNote::factory()->create();
    $voter = User::factory()->create(['reputation' => 50]);
    ReputationEvent::factory()->for($voter)->create(['amount' => 49]);

    castVote($voter, $note, 1);
    $vote = castVote($voter, $note, -1);

    expect($vote?->value)->toBe(-1)
        ->and($voter->votes()->count())->toBe(1)
        ->and($note->refresh()->score)->toBe(-1)
        ->and($note->user->refresh()->reputation)->toBe(1)
        ->and($note->user->reputationEvents()->sole()->amount)->toBe(-2);
});

it('never lets reputation drop below one', function (): void {
    $note = UserNote::factory()->create();

    castVote(User::factory()->create(['reputation' => 50]), $note, -1);

    expect($note->user->refresh()->reputation)->toBe(1);
});

it('refuses votes on your own contributions', function (): void {
    $note = UserNote::factory()->create();

    castVote($note->user, $note, 1);
})->throws(ValidationException::class, 'You cannot vote on your own contributions.');

it('needs the privilege to vote', function (int $value, string $message): void {
    config(['kognize.privileges.vote_up' => 15]);

    castVote(User::factory()->create(), UserNote::factory()->create(), $value);
})->throws(ValidationException::class)->with([
    'upvote' => [1, 'You need 15 reputation to upvote.'],
    'downvote' => [-1, 'You need 50 reputation to downvote.'],
]);

it('explains which privilege is missing', function (): void {
    try {
        castVote(User::factory()->create(), UserNote::factory()->create(), -1);
    } catch (ValidationException $validationException) {
        expect($validationException->errors())->toBe(['vote' => ['You need 50 reputation to downvote.']]);

        return;
    }

    $this->fail('No exception was thrown.');
});

it('gives no reputation for votes between people who invited one another', function (bool $authorInvitedVoter): void {
    $author = User::factory()->create();
    $voter = User::factory()->create($authorInvitedVoter ? ['referrer_id' => $author->id] : []);

    if (! $authorInvitedVoter) {
        $author->update(['referrer_id' => $voter->id]);
    }

    $note = UserNote::factory()->for($author)->create();

    castVote($voter, $note, 1);

    expect($note->refresh()->score)->toBe(1)
        ->and($author->refresh()->reputation)->toBe(1)
        ->and($author->reputationEvents()->count())->toBe(0);
})->with(['referee votes' => true, 'referrer votes' => false]);

it('caps how much reputation upvotes earn per day', function (): void {
    config(['kognize.reputation.daily_cap' => 25]);
    $author = User::factory()->create();
    $notes = UserNote::factory()->for($author)->count(4)->create();

    foreach ($notes as $note) {
        castVote(User::factory()->create(), $note, 1);
    }

    expect($author->refresh()->reputation)->toBe(26)
        ->and($author->reputationEvents()->pluck('amount')->all())->toBe([10, 10, 5]);

    $this->travel(1)->day();
    castVote(User::factory()->create(), $notes[0], 1);

    expect($author->refresh()->reputation)->toBe(36);
});
