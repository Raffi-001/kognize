<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Privilege;
use App\Enums\ReputationReason;
use App\Models\Comment;
use App\Models\Evidence;
use App\Models\User;
use App\Models\UserNote;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Casts, changes or (by repeating it) takes back a vote, and moves the
 * reputation of the author and voter accordingly.
 */
final readonly class CastVote
{
    public function __construct(private RecalculateReputation $reputation) {}

    public function handle(User $voter, UserNote|Comment|Evidence $votable, int $value): ?Vote
    {
        $author = $votable->user;
        $privilege = $value > 0 ? Privilege::VoteUp : Privilege::VoteDown;

        throw_if($author->is($voter), ValidationException::withMessages([
            'vote' => 'You cannot vote on your own contributions.',
        ]));

        throw_unless($voter->hasPrivilege($privilege), ValidationException::withMessages([
            'vote' => sprintf('You need %d reputation to %s.', $privilege->threshold(), $value > 0 ? 'upvote' : 'downvote'),
        ]));

        $vote = DB::transaction(function () use ($voter, $votable, $author, $value): ?Vote {
            $existing = $votable->votes()->where('user_id', $voter->id)->lockForUpdate()->first();

            if ($existing instanceof Vote && $existing->value === $value) {
                $existing->delete();
                $votable->decrement('score', $value);

                return null;
            }

            if ($existing instanceof Vote) {
                $existing->reputationEvents()->delete();
                $existing->update(['value' => $value]);
                $votable->increment('score', 2 * $value);
                $vote = $existing;
            } else {
                $vote = $votable->votes()->create(['user_id' => $voter->id, 'value' => $value]);
                $votable->increment('score', $value);
            }

            $this->record($vote, $voter, $author, $votable);

            return $vote;
        });

        $this->reputation->handle($author);
        $this->reputation->handle($voter);

        return $vote;
    }

    private function record(Vote $vote, User $voter, User $author, UserNote|Comment|Evidence $votable): void
    {
        $reason = ReputationReason::forVote($votable->getMorphClass(), $vote->value);
        // People who invited one another cannot farm reputation for each other.
        $related = $author->referrer_id === $voter->id || $voter->referrer_id === $author->id;
        $amount = $related ? 0 : $reason->points();

        if ($amount > 0 && $reason->isCapped()) {
            $earnedToday = (int) $author->reputationEvents()
                ->whereIn('reason', array_filter(ReputationReason::cases(), fn (ReputationReason $reason): bool => $reason->isCapped()))
                ->where('created_at', '>=', today())
                ->sum('amount');

            $amount = min($amount, max(0, config()->integer('kognize.reputation.daily_cap') - $earnedToday));
        }

        if ($amount !== 0) {
            $vote->reputationEvents()->create(['user_id' => $author->id, 'reason' => $reason, 'amount' => $amount]);
        }

        if ($vote->value < 0 && ! $votable instanceof Comment) {
            $vote->reputationEvents()->create([
                'user_id' => $voter->id,
                'reason' => ReputationReason::DownvoteCast,
                'amount' => ReputationReason::DownvoteCast->points(),
            ]);
        }
    }
}
