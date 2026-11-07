<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserNote;

test('to array', function (): void {
    $note = UserNote::factory()->create()->refresh();

    expect(array_keys($note->toArray()))
        ->toBe([
            'id',
            'submission_id',
            'user_id',
            'annotation_id',
            'label',
            'text',
            'created_at',
            'updated_at',
        ]);
});

it('belongs to a submission and a user and has comments', function (): void {
    $submission = Submission::factory()->create();
    $user = User::factory()->create();
    $note = UserNote::factory()->for($submission)->for($user)->create();
    $comment = Comment::factory()->for($note, 'commentable')->create();

    expect($note->submission->is($submission))->toBeTrue()
        ->and($note->user->is($user))->toBeTrue()
        ->and($note->comments->sole()->is($comment))->toBeTrue()
        ->and($comment->commentable_type)->toBe('user_note');
});
