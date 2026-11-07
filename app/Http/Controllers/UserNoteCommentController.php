<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateComment;
use App\Http\Requests\CreateCommentRequest;
use App\Models\User;
use App\Models\UserNote;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class UserNoteCommentController
{
    public function store(CreateCommentRequest $request, UserNote $userNote, #[CurrentUser] User $user, CreateComment $action): RedirectResponse
    {
        $action->handle($userNote, $user, $request->string('text')->value());

        return back();
    }
}
