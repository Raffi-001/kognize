<?php

declare(strict_types=1);

use App\Models\AnalysisNote;
use App\Models\Comment;
use App\Models\User;

test('to array', function (): void {
    $comment = Comment::factory()->create()->refresh();

    expect(array_keys($comment->toArray()))
        ->toBe([
            'id',
            'commentable_type',
            'commentable_id',
            'user_id',
            'text',
            'created_at',
            'updated_at',
            'score',
        ]);
});

it('belongs to a commentable and a user', function (): void {
    $note = AnalysisNote::factory()->create();
    $user = User::factory()->create();
    $comment = Comment::factory()->for($note, 'commentable')->for($user)->create();

    expect($comment->commentable)->toBeInstanceOf(AnalysisNote::class)
        ->and($comment->commentable->is($note))->toBeTrue()
        ->and($comment->user->is($user))->toBeTrue()
        ->and($comment->commentable_type)->toBe('analysis_note');
});
