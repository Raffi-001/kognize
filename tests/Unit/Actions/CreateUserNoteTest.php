<?php

declare(strict_types=1);

use App\Actions\CreateUserNote;
use App\Models\Submission;
use App\Models\User;

it('creates a user note', function (): void {
    $submission = Submission::factory()->create();
    $user = User::factory()->create();

    $note = resolve(CreateUserNote::class)->handle($submission, $user, '  A note  ', '  Quoted phrase  ');

    expect($note->submission->is($submission))->toBeTrue()
        ->and($note->user->is($user))->toBeTrue()
        ->and($note->text)->toBe('A note')
        ->and($note->label)->toBe('Quoted phrase')
        ->and($note->annotation_id)->toMatch('/^un-[a-z0-9]{12}$/');
});

it('stores an empty label as null', function (?string $label): void {
    $note = resolve(CreateUserNote::class)->handle(
        Submission::factory()->create(),
        User::factory()->create(),
        'A note',
        $label,
    );

    expect($note->refresh()->label)->toBeNull();
})->with([null, '   ']);

it('notifies the author of the analyzed text', function (): void {
    $submission = Submission::factory()->create();
    $user = User::factory()->create();

    resolve(CreateUserNote::class)->handle($submission, $user, 'Context');
    resolve(CreateUserNote::class)->handle($submission, $submission->user, 'My own note');

    $notification = $submission->user->notifications()->sole();

    expect($notification->type)->toBe('community_note_added')
        ->and($notification->data['actors'])->toBe([['id' => $user->id, 'name' => $user->name]])
        ->and($notification->data['excerpt'])->toBe('Context');
});
