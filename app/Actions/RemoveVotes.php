<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReputationEvent;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Builder;

/**
 * Removes votes, for example because the content or the voter is being
 * deleted, and gives back or takes away the reputation they caused.
 */
final readonly class RemoveVotes
{
    public function __construct(private RecalculateReputation $reputation) {}

    /**
     * @param  Builder<Vote>  $votes
     */
    public function handle(Builder $votes): void
    {
        $votes = $votes->with('votable')->get();

        if ($votes->isEmpty()) {
            return;
        }

        $affected = ReputationEvent::query()->whereIn('vote_id', $votes->modelKeys())->distinct()->pluck('user_id');

        $votes->each(function (Vote $vote): void {
            $vote->votable?->decrement('score', $vote->value);
            $vote->delete();
        });

        User::query()->whereKey($affected)->each(fn (User $user): User => $this->reputation->handle($user));
    }
}
