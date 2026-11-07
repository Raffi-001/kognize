<?php

declare(strict_types=1);

use App\Actions\CreateUrlSubmission;
use App\Enums\UrlSubmissionStatus;
use App\Jobs\ProcessUrlSubmissionJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('creates a url submission and dispatches its processing', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $urlSubmission = resolve(CreateUrlSubmission::class)->handle($user, 'https://example.com/article');

    expect($urlSubmission->user->is($user))->toBeTrue()
        ->and($urlSubmission->url)->toBe('https://example.com/article')
        ->and($urlSubmission->refresh()->status)->toBe(UrlSubmissionStatus::Pending);

    Queue::assertPushed(ProcessUrlSubmissionJob::class, fn (ProcessUrlSubmissionJob $job): bool => $job->urlSubmission->is($urlSubmission));
});
