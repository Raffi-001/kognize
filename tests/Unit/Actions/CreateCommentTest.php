<?php

declare(strict_types=1);

use App\Actions\CreateComment;
use App\Models\AnalysisNote;
use App\Models\User;
use App\Models\UserNote;

it('creates a comment', function (Closure $commentable, string $type): void {
    $note = $commentable();
    $user = User::factory()->create();

    $comment = resolve(CreateComment::class)->handle($note, $user, '  Nice catch  ');

    expect($comment->text)->toBe('Nice catch')
        ->and($comment->user->is($user))->toBeTrue()
        ->and($comment->commentable_type)->toBe($type)
        ->and($comment->commentable->is($note))->toBeTrue();
})->with([
    'analysis note' => [fn () => AnalysisNote::factory()->create(), 'analysis_note'],
    'user note' => [fn () => UserNote::factory()->create(), 'user_note'],
]);

it('notifies the note author and everyone already in the thread', function (): void {
    $note = UserNote::factory()->create();
    $earlier = User::factory()->create();
    $commenter = User::factory()->create(['name' => 'Ani']);
    resolve(CreateComment::class)->handle($note, $earlier, 'First');
    $note->user->notifications()->delete();

    resolve(CreateComment::class)->handle($note, $commenter, 'Second');

    $notification = $note->user->notifications()->sole();

    expect($notification->type)->toBe('comment_posted')
        ->and($notification->data['subject'])->toBe(['type' => 'user_note', 'id' => $note->id, 'title' => $note->submission->displayTitle()])
        ->and($notification->data['url'])->toBe(route('submissions.show', $note->submission))
        ->and($notification->data['excerpt'])->toBe('Second')
        ->and($earlier->notifications()->count())->toBe(1)
        ->and($commenter->notifications()->count())->toBe(0);
});

it('notifies the author of the analyzed text about comments on its findings', function (): void {
    $note = AnalysisNote::factory()->create();
    $owner = $note->analysis->submission->user;

    resolve(CreateComment::class)->handle($note, User::factory()->create(), 'Is this right?');

    expect($owner->notifications()->sole()->data['subject']['type'])->toBe('analysis_note');
});
