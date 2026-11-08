<?php

declare(strict_types=1);

use App\Enums\Badge;
use App\Enums\ReputationReason;
use App\Models\Comment;
use App\Models\ReputationEvent;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserNote;
use App\Models\Vote;

it('belongs to a voter and voted content, and records reputation', function (): void {
    $note = UserNote::factory()->create();
    $vote = Vote::factory()->create(['votable_type' => 'user_note', 'votable_id' => $note->id, 'value' => -1]);
    $event = ReputationEvent::factory()->for($vote)->create(['reason' => ReputationReason::NoteDownvoted, 'amount' => -2]);

    expect($vote->refresh()->value)->toBe(-1)
        ->and($vote->user)->toBeInstanceOf(User::class)
        ->and($vote->votable->is($note))->toBeTrue()
        ->and($vote->reputationEvents->sole()->is($event))->toBeTrue()
        ->and($event->refresh()->reason)->toBe(ReputationReason::NoteDownvoted)
        ->and($event->user)->toBeInstanceOf(User::class)
        ->and($event->vote->is($vote))->toBeTrue()
        ->and($vote->toArray())->toHaveKeys(['id', 'user_id', 'votable_type', 'votable_id', 'value']);
});

it('remembers badges and what they were earned for', function (): void {
    $note = UserNote::factory()->create();
    $badge = UserBadge::factory()->create(['badge' => Badge::GoodNote, 'subject_type' => 'user_note', 'subject_id' => $note->id]);
    $plain = UserBadge::factory()->create();

    expect($badge->refresh()->badge)->toBe(Badge::GoodNote)
        ->and($badge->subject->is($note))->toBeTrue()
        ->and($badge->user)->toBeInstanceOf(User::class)
        ->and($plain->refresh()->subject)->toBeNull();
});

it('knows how the viewer voted once votes were loaded for them', function (): void {
    $comment = Comment::factory()->create();
    $voter = User::factory()->create();
    Vote::factory()->for($voter)->create(['votable_type' => 'comment', 'votable_id' => $comment->id, 'value' => 1]);

    expect($comment->voteOf($voter->id))->toBeNull();

    $comment->load('votes');

    expect($comment->voteOf($voter->id))->toBe(1)
        ->and($comment->voteOf(User::factory()->create()->id))->toBeNull()
        ->and($comment->voteOf(null))->toBeNull();
});
