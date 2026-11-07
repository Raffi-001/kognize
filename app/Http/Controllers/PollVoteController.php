<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CastPollVote;
use App\Http\Requests\CreatePollVoteRequest;
use App\Models\Poll;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class PollVoteController
{
    public function store(CreatePollVoteRequest $request, Poll $poll, #[CurrentUser] User $user, CastPollVote $action): RedirectResponse
    {
        $action->handle(
            $poll->options()->whereKey($request->integer('poll_option_id'))->sole(),
            $user,
            $request->filled('reason') ? $request->string('reason')->value() : null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Thanks, your answer was recorded.')]);

        return back();
    }
}
