<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PollStatus;
use App\Models\Poll;

final readonly class ClosePoll
{
    public function handle(Poll $poll): Poll
    {
        $poll->update([
            'status' => PollStatus::Closed,
            'closed_at' => now(),
        ]);

        return $poll;
    }
}
