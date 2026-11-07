<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\User;
use App\Models\UserNote;

it('requires authentication', function (): void {
    $note = UserNote::factory()->create();

    $this->post(route('user-notes.comments.store', $note), ['text' => 'Comment'])
        ->assertRedirectToRoute('login');
});

it('may comment on a user note', function (): void {
    $user = User::factory()->create();
    $note = UserNote::factory()->create();

    $this->actingAs($user)
        ->fromRoute('submissions.show', $note->submission)
        ->post(route('user-notes.comments.store', $note), ['text' => 'Comment'])
        ->assertRedirectToRoute('submissions.show', $note->submission);

    $comment = Comment::query()->sole();

    expect($comment->commentable->is($note))->toBeTrue()
        ->and($comment->user->is($user))->toBeTrue()
        ->and($comment->text)->toBe('Comment');
});

it('validates the comment', function (): void {
    $note = UserNote::factory()->create();

    $this->actingAs(User::factory()->create())
        ->fromRoute('dashboard')
        ->post(route('user-notes.comments.store', $note), [])
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHasErrors('text');
});
