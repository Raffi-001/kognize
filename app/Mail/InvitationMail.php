<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class InvitationMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Invitation $invitation,
        public readonly User $inviter,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('%s invited you to %s', $this->inviter->name, config()->string('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invitation',
            with: [
                'registrationUrl' => route('register', ['invitation' => $this->invitation->code]),
            ],
        );
    }
}
