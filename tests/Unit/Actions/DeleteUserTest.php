<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Actions\DeleteUser;
use App\Models\Comment;
use App\Models\Evidence;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserNote;
use App\Models\Vote;

it('may delete a user', function (): void {
    $user = User::factory()->create();

    $action = resolve(DeleteUser::class);

    $action->handle($user);

    expect($user->exists)->toBeFalse();
});

it('removes the votes a user cast and the votes on what they wrote', function (): void {
    $user = User::factory()->create();
    $theirs = UserNote::factory()->for($user)->create();
    $theirComment = Comment::factory()->for($user)->create();
    $theirSource = Evidence::factory()->for($user)->create();
    $other = UserNote::factory()->create();
    $ownSubmission = Submission::factory()->for($user)->create();
    $noteOnTheirs = UserNote::factory()->for($ownSubmission)->create();
    $fan = User::factory()->create();

    $vote = resolve(CastVote::class);
    $vote->handle($user, $other, 1);
    foreach ([$theirs, $theirComment, $theirSource, $noteOnTheirs] as $votable) {
        $vote->handle($fan, $votable, 1);
    }

    resolve(DeleteUser::class)->handle($user);

    expect(Vote::query()->count())->toBe(0)
        ->and($other->refresh()->score)->toBe(0)
        ->and($other->user->refresh()->reputation)->toBe(1)
        ->and($noteOnTheirs->user->refresh()->reputation)->toBe(1)
        ->and(Submission::query()->whereKey($ownSubmission->id)->exists())->toBeFalse();
});
