<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Comment;
use App\Models\Evidence;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserNote;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Deletes the account. Votes the user cast and votes on what they wrote are
 * removed first, so everyone else's reputation stays accurate.
 */
final readonly class DeleteUser
{
    public function __construct(
        private DeleteSubmission $deleteSubmission,
        private RemoveVotes $removeVotes,
    ) {}

    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->submissions()->each(fn (Submission $submission) => $this->deleteSubmission->handle($submission));

            $this->removeVotes->handle(Vote::query()->where(fn (Builder $query) => $query
                ->where('user_id', $user->id)
                ->orWhere(fn (Builder $query) => $query->where('votable_type', 'user_note')->whereIn('votable_id', UserNote::query()->whereBelongsTo($user)->select('id')))
                ->orWhere(fn (Builder $query) => $query->where('votable_type', 'comment')->whereIn('votable_id', Comment::query()->whereBelongsTo($user)->select('id')))
                ->orWhere(fn (Builder $query) => $query->where('votable_type', 'evidence')->whereIn('votable_id', Evidence::query()->whereBelongsTo($user)->select('id')))));

            $user->delete();
        });
    }
}
