<?php

declare(strict_types=1);

use App\Actions\MarkUrlSubmissionFailed;
use App\Enums\UrlSubmissionStatus;
use App\Models\UrlSubmission;

it('marks a url submission as failed', function (): void {
    $urlSubmission = UrlSubmission::factory()->create();

    resolve(MarkUrlSubmissionFailed::class)->handle($urlSubmission, 'Something broke.');

    expect($urlSubmission->refresh()->status)->toBe(UrlSubmissionStatus::Failed)
        ->and($urlSubmission->error_message)->toBe('Something broke.');
});
