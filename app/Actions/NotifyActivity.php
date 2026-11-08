<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationKind;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * Sends an in-app notification to everyone involved, except the person who
 * caused it and anyone who muted that kind. Repeat activity on the same
 * subject within the hour updates the unread notification instead of
 * stacking new ones.
 */
final readonly class NotifyActivity
{
    private const int GROUP_MINUTES = 60;

    /**
     * @param  iterable<int, User|null>  $recipients
     * @param  array{type: string, id: int|string, title: string}  $subject
     */
    public function handle(iterable $recipients, NotificationKind $kind, ?User $actor, array $subject, string $url, ?string $excerpt = null): void
    {
        $notification = new ActivityNotification(
            $kind,
            $actor instanceof User ? ['id' => $actor->id, 'name' => $actor->name] : null,
            [...$subject, 'title' => Str::limit(Str::squish($subject['title']), 80)],
            $url,
            $excerpt === null ? null : Str::limit(Str::squish($excerpt), 160),
        );

        collect($recipients)
            ->whereInstanceOf(User::class)
            ->unique('id')
            ->reject(fn (User $recipient): bool => $recipient->id === $actor?->id)
            ->filter(fn (User $recipient): bool => $recipient->wantsNotification($kind))
            ->each(fn (User $recipient) => $this->deliver($recipient, $notification));
    }

    private function deliver(User $recipient, ActivityNotification $notification): void
    {
        $existing = $notification->kind->groups() && $notification->actor !== null
            ? $recipient->unreadNotifications()
                ->where('type', $notification->kind->value)
                ->where('data->subject->type', $notification->subject['type'])
                ->where('data->subject->id', $notification->subject['id'])
                ->where('created_at', '>=', now()->subMinutes(self::GROUP_MINUTES))
                ->first()
            : null;

        if (! $existing instanceof DatabaseNotification) {
            $recipient->notify($notification);

            return;
        }

        /** @var array<string, mixed> $data */
        $data = $existing->data;
        $previous = is_array($data['actors'] ?? null) ? $data['actors'] : [];

        $existing->forceFill([
            'data' => [
                ...$data,
                'actors' => collect([$notification->actor, ...$previous])->unique('id')->values()->all(),
                'excerpt' => $notification->excerpt,
            ],
            'created_at' => now(),
        ])->save();
    }
}
