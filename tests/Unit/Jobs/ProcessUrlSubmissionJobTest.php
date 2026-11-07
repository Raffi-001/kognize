<?php

declare(strict_types=1);

use App\Enums\UrlSubmissionStatus;
use App\Jobs\ProcessUrlSubmissionJob;
use App\Models\UrlSubmission;
use Illuminate\Support\Facades\Http;

it('processes the url submission', function (): void {
    Http::fake(['https://example.com/article' => Http::response('', 404)]);

    $urlSubmission = UrlSubmission::factory()->create(['url' => 'https://example.com/article']);

    dispatch_sync(new ProcessUrlSubmissionJob($urlSubmission));

    expect($urlSubmission->refresh()->status)->toBe(UrlSubmissionStatus::Failed)
        ->and($urlSubmission->error_message)->toBe('The page could not be downloaded.');
});

it('marks the url submission as failed when the job fails', function (): void {
    $urlSubmission = UrlSubmission::factory()->create();

    new ProcessUrlSubmissionJob($urlSubmission)->failed();

    expect($urlSubmission->refresh()->status)->toBe(UrlSubmissionStatus::Failed)
        ->and($urlSubmission->error_message)->toBe('Something went wrong while processing this URL.');
});
