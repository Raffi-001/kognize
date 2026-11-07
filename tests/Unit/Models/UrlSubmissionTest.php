<?php

declare(strict_types=1);

use App\Enums\UrlSubmissionStatus;
use App\Models\Submission;
use App\Models\UrlSubmission;
use App\Models\User;

test('to array', function (): void {
    $urlSubmission = UrlSubmission::factory()->create()->refresh();

    expect(array_keys($urlSubmission->toArray()))
        ->toBe([
            'id',
            'user_id',
            'submission_id',
            'url',
            'status',
            'error_message',
            'created_at',
            'updated_at',
        ]);
});

it('defaults to pending', function (): void {
    expect(new UrlSubmission()->status)->toBe(UrlSubmissionStatus::Pending);
});

it('belongs to a user and a submission', function (): void {
    $user = User::factory()->create();
    $submission = Submission::factory()->create();
    $urlSubmission = UrlSubmission::factory()->for($user)->for($submission)->create();

    expect($urlSubmission->user->is($user))->toBeTrue()
        ->and($urlSubmission->submission?->is($submission))->toBeTrue();
});
