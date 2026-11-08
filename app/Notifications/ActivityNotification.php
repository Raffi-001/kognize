<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationKind;
use Illuminate\Notifications\Notification;

/**
 * One in-app notification about something that happened on Kognize. The
 * kind is stored as the notification type so similar ones can be grouped.
 */
final class ActivityNotification extends Notification
{
    /**
     * @param  array{id: string, name: string}|null  $actor
     * @param  array{type: string, id: int|string, title: string}  $subject
     */
    public function __construct(
        public readonly NotificationKind $kind,
        public readonly ?array $actor,
        public readonly array $subject,
        public readonly string $url,
        public readonly ?string $excerpt = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return $this->kind->value;
    }

    /**
     * @return array{actors: list<array{id: string, name: string}>, subject: array{type: string, id: int|string, title: string}, url: string, excerpt: string|null}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'actors' => $this->actor === null ? [] : [$this->actor],
            'subject' => $this->subject,
            'url' => $this->url,
            'excerpt' => $this->excerpt,
        ];
    }
}
