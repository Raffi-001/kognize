<?php

declare(strict_types=1);

use App\Enums\NotificationKind;
use App\Models\User;
use App\Notifications\ActivityNotification;

it('is stored in the database under its kind', function (): void {
    $user = User::factory()->make();
    $notification = new ActivityNotification(
        NotificationKind::CommentPosted,
        ['id' => 'actor', 'name' => 'Ani'],
        ['type' => 'user_note', 'id' => 4, 'title' => 'Coffee ad'],
        'https://kognize.test/submissions/1',
        'Nice catch',
    );

    expect($notification->via($user))->toBe(['database'])
        ->and($notification->databaseType($user))->toBe('comment_posted')
        ->and($notification->toArray($user))->toBe([
            'actors' => [['id' => 'actor', 'name' => 'Ani']],
            'subject' => ['type' => 'user_note', 'id' => 4, 'title' => 'Coffee ad'],
            'url' => 'https://kognize.test/submissions/1',
            'excerpt' => 'Nice catch',
        ]);
});

it('has no actors when the system caused it', function (): void {
    $notification = new ActivityNotification(
        NotificationKind::AnalysisCompleted,
        null,
        ['type' => 'submission', 'id' => 1, 'title' => 'Coffee ad'],
        'https://kognize.test/submissions/1',
    );

    expect($notification->toArray(User::factory()->make()))->toMatchArray([
        'actors' => [],
        'excerpt' => null,
    ]);
});
