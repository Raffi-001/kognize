<?php

declare(strict_types=1);

use App\Models\AnalysisNote;
use App\Models\Comment;
use App\Models\User;

it('requires authentication', function (): void {
    $note = AnalysisNote::factory()->create();

    $this->post(route('analysis-notes.comments.store', $note), ['text' => 'Comment'])
        ->assertRedirectToRoute('login');
});

it('may comment on an analysis note', function (): void {
    $user = User::factory()->create();
    $note = AnalysisNote::factory()->create();
    $submission = $note->analysis->submission;

    $this->actingAs($user)
        ->fromRoute('submissions.show', $submission)
        ->post(route('analysis-notes.comments.store', $note), ['text' => 'Comment'])
        ->assertRedirectToRoute('submissions.show', $submission);

    $comment = Comment::query()->sole();

    expect($comment->commentable->is($note))->toBeTrue()
        ->and($comment->user->is($user))->toBeTrue()
        ->and($comment->text)->toBe('Comment');
});

it('validates the comment', function (array $data): void {
    $note = AnalysisNote::factory()->create();

    $this->actingAs(User::factory()->create())
        ->fromRoute('dashboard')
        ->post(route('analysis-notes.comments.store', $note), $data)
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHasErrors('text');

    expect(Comment::query()->count())->toBe(0);
})->with([
    'missing' => [[]],
    'too long' => [['text' => str_repeat('a', 1001)]],
]);
