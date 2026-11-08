<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Evidence;
use App\Models\User;
use App\Models\UserNote;

it('requires authentication', function (): void {
    $this->post(route('votes.store'), [])->assertRedirectToRoute('login');
});

it('votes on community notes, replies and sources', function (string $type, Closure $votable): void {
    $content = $votable();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->fromRoute('dashboard')
        ->post(route('votes.store'), ['votable_type' => $type, 'votable_id' => $content->id, 'value' => 1])
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHasNoErrors();

    expect($content->refresh()->score)->toBe(1)
        ->and($content->user->refresh()->reputation)->toBeGreaterThan(1);
})->with([
    ['user_note', fn () => UserNote::factory()->create()],
    ['comment', fn () => Comment::factory()->create()],
    ['evidence', fn () => Evidence::factory()->create()],
]);

it('validates the vote', function (array $data, string $error): void {
    $this->actingAs(User::factory()->create())
        ->fromRoute('dashboard')
        ->post(route('votes.store'), $data)
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHasErrors($error);
})->with([
    'unknown type' => [['votable_type' => 'submission', 'votable_id' => 1, 'value' => 1], 'votable_type'],
    'bad value' => [['votable_type' => 'user_note', 'votable_id' => 1, 'value' => 2], 'value'],
    'missing content' => [['votable_type' => 'user_note', 'votable_id' => 999, 'value' => 1], 'vote'],
]);

it('explains why a vote is not allowed', function (): void {
    $note = UserNote::factory()->create();

    $this->actingAs($note->user)
        ->fromRoute('dashboard')
        ->post(route('votes.store'), ['votable_type' => 'user_note', 'votable_id' => $note->id, 'value' => 1])
        ->assertSessionHasErrors(['vote' => 'You cannot vote on your own contributions.']);
});
