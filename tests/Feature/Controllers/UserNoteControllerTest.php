<?php

declare(strict_types=1);

use App\Models\Submission;
use App\Models\User;
use App\Models\UserNote;

it('requires authentication', function (): void {
    $submission = Submission::factory()->create();

    $this->post(route('submissions.user-notes.store', $submission), ['text' => 'Note'])
        ->assertRedirectToRoute('login');
});

it('may add a note to a submission', function (): void {
    $user = User::factory()->create();
    $submission = Submission::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('submissions.show', $submission)
        ->post(route('submissions.user-notes.store', $submission), [
            'text' => 'My note',
            'label' => 'Quoted phrase',
        ]);

    $response->assertRedirectToRoute('submissions.show', $submission)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Note added.')]);

    $note = UserNote::query()->sole();

    expect($note->submission->is($submission))->toBeTrue()
        ->and($note->user->is($user))->toBeTrue()
        ->and($note->text)->toBe('My note')
        ->and($note->label)->toBe('Quoted phrase');
});

it('may add a note without a label', function (): void {
    $submission = Submission::factory()->create();

    $this->actingAs(User::factory()->create())
        ->fromRoute('submissions.show', $submission)
        ->post(route('submissions.user-notes.store', $submission), ['text' => 'My note'])
        ->assertSessionHasNoErrors();

    expect(UserNote::query()->sole()->label)->toBeNull();
});

it('validates the note', function (array $data, string $error): void {
    $submission = Submission::factory()->create();

    $this->actingAs(User::factory()->create())
        ->fromRoute('submissions.show', $submission)
        ->post(route('submissions.user-notes.store', $submission), $data)
        ->assertRedirectToRoute('submissions.show', $submission)
        ->assertSessionHasErrors($error);

    expect(UserNote::query()->count())->toBe(0);
})->with([
    'missing text' => [[], 'text'],
    'long text' => [['text' => str_repeat('a', 2001)], 'text'],
    'long label' => [['text' => 'Note', 'label' => str_repeat('a', 1001)], 'label'],
]);
