<?php

declare(strict_types=1);

use App\Enums\NotificationKind;
use App\Mail\NotificationDigestMail;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Mail;

function digestNotification(): ActivityNotification
{
    return new ActivityNotification(
        NotificationKind::CommentPosted,
        ['id' => 'someone', 'name' => 'Ani'],
        ['type' => 'user_note', 'id' => 1, 'title' => 'Coffee ad'],
        'https://kognize.test',
        'Nice catch',
    );
}

it('emails a summary of the last day of unread notifications to opted-in users', function (): void {
    Mail::fake();

    $subscriber = User::factory()->create(['notification_digest' => true]);
    $subscriber->notify(digestNotification());

    $stale = User::factory()->create(['notification_digest' => true]);
    $stale->notify(digestNotification());
    $stale->notifications()->update(['created_at' => now()->subDays(2)]);

    $read = User::factory()->create(['notification_digest' => true]);
    $read->notify(digestNotification());
    $read->unreadNotifications->markAsRead();

    User::factory()->create(['notification_digest' => false])->notify(digestNotification());
    User::factory()->unverified()->create(['notification_digest' => true])->notify(digestNotification());

    $this->artisan('kognize:send-notification-digests')
        ->expectsOutputToContain('Queued 1 notification digest.')
        ->assertSuccessful();

    Mail::assertQueuedCount(1);
    Mail::assertQueued(NotificationDigestMail::class, fn (NotificationDigestMail $mail): bool => $mail->hasTo($subscriber->email)
        && $mail->items === [['headline' => 'Ani commented on “Coffee ad”', 'excerpt' => 'Nice catch']]);
});

it('reports how many digests it queued', function (): void {
    Mail::fake();

    foreach (range(1, 2) as $ignored) {
        User::factory()->create(['notification_digest' => true])->notify(digestNotification());
    }

    $this->artisan('kognize:send-notification-digests')
        ->expectsOutputToContain('Queued 2 notification digests.')
        ->assertSuccessful();
});

it('is scheduled every morning', function (): void {
    $event = collect(resolve(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'kognize:send-notification-digests'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 8 * * *')
        ->and($event->timezone)->toBe('Asia/Yerevan');
});
