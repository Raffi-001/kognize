<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class NotificationDigestMail extends Mailable implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<array{headline: string, excerpt: string|null}>  $items
     */
    public function __construct(
        public readonly User $user,
        public readonly array $items,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('%d new on %s', count($this->items), config()->string('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.notification-digest',
            with: [
                'notificationsUrl' => route('notifications.index'),
                'settingsUrl' => route('notification-preferences.edit'),
            ],
        );
    }
}
