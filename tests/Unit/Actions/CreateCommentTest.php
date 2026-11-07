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
