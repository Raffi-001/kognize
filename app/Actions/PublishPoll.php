<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PollStatus;
use App\Models\Poll;
use App\Models\User;

final readonly class PublishPoll
{
    public function handle(Poll $poll, User $admin): Poll
    {
        $poll->update([
            'status' => PollStatus::Published,
            'approved_by' => $admin->id,
            'published_at' => now(),
            'closed_at' => null,
        ]);

        return $poll;
    }
}
