<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUrlSubmission;
use App\Http\Requests\CreateUrlSubmissionRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class UrlSubmissionController
{
    public function store(CreateUrlSubmissionRequest $request, #[CurrentUser] User $user, CreateUrlSubmission $action): RedirectResponse
    {
        $urlSubmission = $action->handle($user, $request->string('url')->value());

        return to_route('dashboard', ['link' => $urlSubmission->id]);
    }
}
