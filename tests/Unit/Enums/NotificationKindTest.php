<?php

declare(strict_types=1);

use App\Enums\NotificationKind;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

it('describes every kind', function (NotificationKind $kind): void {
    expect($kind->label())->toBeString()->not->toBeEmpty()
        ->and($kind->description())->toBeString()->toEndWith('.');
})->with(NotificationKind::cases());

it('groups only repeat social activity', function (): void {
    $grouped = array_values(array_filter(NotificationKind::cases(), fn (NotificationKind $kind): bool => $kind->groups()));

    expect($grouped)->toBe([NotificationKind::CommentPosted, NotificationKind::CommunityNoteAdded]);
});

it('writes a headline for every kind', function (NotificationKind $kind, string $headline): void {
    expect($kind->headline(['Ani'], 'Coffee ad'))->toBe($headline);
})->with([
    [NotificationKind::AnalysisCompleted, 'Your analysis of “Coffee ad” is ready'],
    [NotificationKind::AnalysisFailed, 'The analysis of “Coffee ad” could not be completed'],
    [NotificationKind::CommentPosted, 'Ani commented on “Coffee ad”'],
    [NotificationKind::CommunityNoteAdded, 'Ani added a community note to “Coffee ad”'],
    [NotificationKind::EvidenceChecked, 'Your source for “Coffee ad” was checked'],
    [NotificationKind::QuestionResolved, '“Coffee ad” was resolved'],
    [NotificationKind::InviteeJoined, 'Ani joined with your invitation'],
]);

it('names the people involved', function (array $actors, string $headline): void {
    expect(NotificationKind::CommentPosted->headline($actors, 'Coffee ad'))->toBe($headline);
})->with([
    'nobody' => [[], 'Someone commented on “Coffee ad”'],
    'two people' => [['Ani', 'Aram'], 'Ani and Aram commented on “Coffee ad”'],
    'many people' => [['Ani', 'Aram', 'Lilit', 'Davit'], 'Ani and 3 others commented on “Coffee ad”'],
]);

it('summarizes a stored notification', function (): void {
    $notification = new DatabaseNotification([
        'id' => (string) Str::uuid(),
        'type' => 'comment_posted',
        'data' => [
            'actors' => [['id' => 'a', 'name' => 'Ani'], ['id' => 'b', 'name' => 'Aram'], 'broken'],
            'subject' => ['type' => 'user_note', 'id' => 1, 'title' => 'Coffee ad'],
            'excerpt' => 'Great point',
        ],
    ]);

    expect(NotificationKind::summarize($notification))->toBe([
        'kind' => NotificationKind::CommentPosted,
        'headline' => 'Ani and Aram commented on “Coffee ad”',
        'excerpt' => 'Great point',
        'actors' => ['Ani', 'Aram'],
    ]);
});

it('summarizes unknown or malformed notifications without failing', function (): void {
    $notification = new DatabaseNotification([
        'id' => (string) Str::uuid(),
        'type' => User::class,
        'data' => ['subject' => 'nope', 'actors' => 'nope', 'excerpt' => 5],
    ]);

    expect(NotificationKind::summarize($notification))->toBe([
        'kind' => null,
        'headline' => '',
        'excerpt' => null,
        'actors' => [],
    ]);
});
