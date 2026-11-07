<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PollStatus;
use App\Models\Poll;
use App\Models\User;

final class PollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Only drafts can change, so published answers always refer to the same wording.
     */
    public function update(User $user, Poll $poll): bool
    {
        return $user->is_admin && $poll->status === PollStatus::Draft;
    }

    public function delete(User $user, Poll $poll): bool
    {
        return $user->is_admin && $poll->status === PollStatus::Draft;
    }
}
