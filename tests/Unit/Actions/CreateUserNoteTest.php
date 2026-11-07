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
