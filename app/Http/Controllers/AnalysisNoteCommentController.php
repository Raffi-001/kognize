<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateComment;
use App\Http\Requests\CreateCommentRequest;
use App\Models\AnalysisNote;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class AnalysisNoteCommentController
{
    public function store(CreateCommentRequest $request, AnalysisNote $analysisNote, #[CurrentUser] User $user, CreateComment $action): RedirectResponse
    {
        $action->handle($analysisNote, $user, $request->string('text')->value());

        return back();
    }
}
