<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Vote;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasVotes
{
    /**
     * @return MorphMany<Vote, $this>
     */
    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    /**
     * The value of the vote the given user cast, when votes were loaded for them.
     */
    public function voteOf(mixed $userId): ?int
    {
        if (! is_string($userId) || ! $this->relationLoaded('votes')) {
            return null;
        }

        return $this->votes->firstWhere('user_id', $userId)?->value;
    }
}
