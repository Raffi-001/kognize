<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUserNote;
use App\Http\Requests\CreateUserNoteRequest;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class UserNoteController
{
    public function store(CreateUserNoteRequest $request, Submission $submission, #[CurrentUser] User $user, CreateUserNote $action): RedirectResponse
    {
        $action->handle(
            $submission,
            $user,
            $request->string('text')->value(),
            $request->filled('label') ? $request->string('label')->value() : null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note added.')]);

        return back();
    }
}
