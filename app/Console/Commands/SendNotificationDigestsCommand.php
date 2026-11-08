<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\NotificationKind;
use App\Mail\NotificationDigestMail;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;

/**
 * Emails each opted-in user a summary of the unread notifications they got
 * in the last day. Scheduled to run once a day.
 */
#[Description('Email the daily summary of unread notifications to users who opted in.')]
#[Signature('kognize:send-notification-digests')]
final class SendNotificationDigestsCommand extends Command
{
    public function handle(): int
    {
        $sent = 0;

        User::query()
            ->where('notification_digest', true)
            ->whereNotNull('email_verified_at')
            ->whereHas('unreadNotifications', fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDay()))
            ->each(function (User $user) use (&$sent): void {
                $items = array_values($user->unreadNotifications()
                    ->where('created_at', '>=', now()->subDay())
                    ->limit(20)
                    ->get()
                    ->map(function (DatabaseNotification $notification): array {
                        $item = NotificationKind::summarize($notification);

                        return ['headline' => $item['headline'], 'excerpt' => $item['excerpt']];
                    })
                    ->all());

                Mail::to($user)->queue(new NotificationDigestMail($user, $items));
                $sent++;
            });

        $this->components->info(sprintf('Queued %d notification %s.', $sent, $sent === 1 ? 'digest' : 'digests'));

        return self::SUCCESS;
    }
}
