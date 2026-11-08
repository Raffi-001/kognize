<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CastVote;
use App\Http\Requests\CreateVoteRequest;
use App\Models\Comment;
use App\Models\Evidence;
use App\Models\User;
use App\Models\UserNote;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class VoteController
{
    public function store(CreateVoteRequest $request, #[CurrentUser] User $user, CastVote $action): RedirectResponse
    {
        $id = $request->integer('votable_id');

        $votable = match ($request->string('votable_type')->value()) {
            'user_note' => UserNote::query()->findOrFail($id),
            'comment' => Comment::query()->findOrFail($id),
            default => Evidence::query()->findOrFail($id),
        };

        $action->handle($user, $votable, $request->integer('value'));

        return back();
    }
}
