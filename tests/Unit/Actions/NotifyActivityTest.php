<?php

declare(strict_types=1);

use App\Actions\NotifyActivity;
use App\Enums\NotificationKind;
use App\Models\User;

function notifyActivity(iterable $recipients, NotificationKind $kind = NotificationKind::CommentPosted, ?User $actor = null, int $subjectId = 1, ?string $excerpt = 'Hello'): void
{
    resolve(NotifyActivity::class)->handle(
        $recipients,
        $kind,
        $actor,
        ['type' => 'user_note', 'id' => $subjectId, 'title' => 'Coffee ad'],
        'https://kognize.test/submissions/1',
        $excerpt,
    );
}

it('notifies each recipient once, skipping the actor and missing users', function (): void {
    $actor = User::factory()->create();
    $recipient = User::factory()->create();

    notifyActivity([$recipient, $recipient, null, $actor], actor: $actor);

    expect($recipient->notifications()->count())->toBe(1)
        ->and($actor->notifications()->count())->toBe(0)
        ->and($recipient->notifications()->sole()->data)->toMatchArray([
            'actors' => [['id' => $actor->id, 'name' => $actor->name]],
            'excerpt' => 'Hello',
        ]);
});

it('skips people who muted the kind', function (): void {
    $muted = User::factory()->create(['muted_notifications' => ['comment_posted']]);
    $other = User::factory()->create(['muted_notifications' => ['analysis_completed']]);

    notifyActivity([$muted, $other], actor: User::factory()->create());

    expect($muted->notifications()->count())->toBe(0)
        ->and($other->notifications()->count())->toBe(1);
});

it('shortens long titles and excerpts', function (): void {
    $recipient = User::factory()->create();

    resolve(NotifyActivity::class)->handle(
        [$recipient],
        NotificationKind::CommentPosted,
        null,
        ['type' => 'user_note', 'id' => 1, 'title' => str_repeat('ա', 100)],
        'https://kognize.test',
        str_repeat("բ \n", 100),
    );

    $data = $recipient->notifications()->sole()->data;

    expect(mb_strlen($data['subject']['title']))->toBe(83)
        ->and(mb_strlen($data['excerpt']))->toBeLessThanOrEqual(163)
        ->and($data['excerpt'])->not->toContain("\n");
});

it('folds repeat activity on the same subject into one unread notification', function (): void {
    $this->freezeSecond();
    $recipient = User::factory()->create();
    $first = User::factory()->create(['name' => 'Ani']);
    $second = User::factory()->create(['name' => 'Aram']);

    notifyActivity([$recipient], actor: $first, excerpt: 'First');
    $this->travel(10)->minutes();
    notifyActivity([$recipient], actor: $second, excerpt: 'Second');
    notifyActivity([$recipient], actor: $first, excerpt: 'Third');

    $notification = $recipient->notifications()->sole();

    expect(array_column($notification->data['actors'], 'name'))->toBe(['Ani', 'Aram'])
        ->and($notification->data['excerpt'])->toBe('Third')
        ->and($notification->created_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

it('starts a new notification once the old one is read, stale or about something else', function (Closure $between, int $subjectId): void {
    $recipient = User::factory()->create();

    notifyActivity([$recipient], actor: User::factory()->create());
    $between($recipient, $this);
    notifyActivity([$recipient], actor: User::factory()->create(), subjectId: $subjectId);

    expect($recipient->notifications()->count())->toBe(2);
})->with([
    'read' => [fn (User $recipient) => $recipient->unreadNotifications->markAsRead(), 1],
    'older than an hour' => [fn (User $recipient, $test) => $test->travel(61)->minutes(), 1],
    'another subject' => [fn (): null => null, 2],
]);

it('does not group kinds that stand on their own, or activity without an actor', function (NotificationKind $kind, bool $withActor): void {
    $recipient = User::factory()->create();

    notifyActivity([$recipient], $kind, $withActor ? User::factory()->create() : null);
    notifyActivity([$recipient], $kind, $withActor ? User::factory()->create() : null);

    expect($recipient->notifications()->count())->toBe(2);
})->with([
    'own kind' => [NotificationKind::EvidenceChecked, true],
    'no actor' => [NotificationKind::CommentPosted, false],
]);
