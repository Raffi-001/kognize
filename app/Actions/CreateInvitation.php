<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

final readonly class CreateInvitation
{
    /**
     * Create a single-use code. With an email address the code is mailed and
     * only works for that address; without one it is an open code to hand out.
     */
    public function handle(User $inviter, ?string $email = null): Invitation
    {
        $invitation = $inviter->invitations()->create([
            'code' => $this->generateCode(),
            'email' => $email === null ? null : mb_strtolower($email),
        ]);

        if ($invitation->email !== null) {
            Mail::to($invitation->email)->send(new InvitationMail($invitation, $inviter));
        }

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
