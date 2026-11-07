<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateInvitation;
use App\Http\Requests\CreateInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InvitationController
{
    public function index(#[CurrentUser] User $user): Response
    {
        return Inertia::render('invitation/Index', [
            'invitations' => InvitationResource::collection(
                $user->invitations()->with('invitee')->latest('id')->get(),
            ),
        ]);
    }

    public function store(CreateInvitationRequest $request, #[CurrentUser] User $user, CreateInvitation $action): RedirectResponse
    {
        $action->handle($user, $request->string('email')->value());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return back();
    }
}
