<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

final readonly class CreateInvitation
{
    public function handle(User $inviter, string $email): Invitation
    {
        $invitation = $inviter->invitations()->create([
            'code' => $this->generateCode(),
            'email' => mb_strtolower($email),
        ]);

        Mail::to($invitation->email)->send(new InvitationMail($invitation, $inviter));

        return $invitation;
    }

    private function generateCode(): string
    {
        do {
            $code = (string) random_int(10000, 99999);
        } while (Invitation::query()->where('code', $code)->exists());

        return $code;
    }
}
