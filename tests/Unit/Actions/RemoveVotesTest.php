<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Actions\RemoveVotes;
use App\Models\User;
use App\Models\UserNote;
use App\Models\Vote;

it('removes votes and the reputation they earned', function (): void {
    $note = UserNote::factory()->create();
    $kept = UserNote::factory()->for($note->user)->create();
    $voter = User::factory()->create();
    resolve(CastVote::class)->handle($voter, $note, 1);
    resolve(CastVote::class)->handle($voter, $kept, 1);

    resolve(RemoveVotes::class)->handle(Vote::query()->where('votable_id', $note->id)->where('votable_type', 'user_note'));

    expect($note->refresh()->score)->toBe(0)
        ->and($kept->refresh()->score)->toBe(1)
        ->and($note->user->refresh()->reputation)->toBe(11)
        ->and(Vote::query()->count())->toBe(1);
});

it('does nothing without votes', function (): void {
    resolve(RemoveVotes::class)->handle(Vote::query());

    expect(Vote::query()->count())->toBe(0);
});
