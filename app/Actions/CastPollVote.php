<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\User;

final readonly class CastPollVote
{
    public function handle(PollOption $option, User $user, ?string $reason = null): PollVote
    {
        return PollVote::query()->updateOrCreate(
            ['poll_id' => $option->poll_id, 'user_id' => $user->id],
            [
                'poll_option_id' => $option->id,
                'reason' => $reason === null || mb_trim($reason) === '' ? null : mb_trim($reason),
            ],
        );
    }
}
