<?php

declare(strict_types=1);

use App\Actions\ProcessUrlSubmission;
use App\Enums\UrlSubmissionStatus;
use App\Models\UrlSubmission;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

it('creates a submission from the extracted article', function (): void {
    Bus::fake();

    $body = config()->string('analysis.sample_text');

    Http::fake([
        'https://example.com/article' => Http::response('<html><head><title>Վերնագիր</title></head><body><article><p>'.$body.'</p></article></body></html>'),
    ]);

    $urlSubmission = UrlSubmission::factory()->create([
        'url' => 'https://example.com/article',
        'error_message' => 'Previous error',
    ]);

    $submission = resolve(ProcessUrlSubmission::class)->handle($urlSubmission);

    expect($submission)->not->toBeNull()
        ->and($submission->user->is($urlSubmission->user))->toBeTrue()
        ->and($submission->title)->toBe('Վերնագիր')
        ->and($submission->body)->toBe($body)
        ->and($submission->source)->toBe('https://example.com/article')
        ->and($urlSubmission->refresh()->status)->toBe(UrlSubmissionStatus::Completed)
        ->and($urlSubmission->submission_id)->toBe($submission->id)
        ->and($urlSubmission->error_message)->toBeNull();

    Bus::assertBatchCount(1);
});

it('fails when the page cannot be downloaded', function (Closure $response): void {
    Http::fake(['https://example.com/article' => $response()]);

    $urlSubmission = UrlSubmission::factory()->create(['url' => 'https://example.com/article']);

    expect(resolve(ProcessUrlSubmission::class)->handle($urlSubmission))->toBeNull()
        ->and($urlSubmission->refresh()->status)->toBe(UrlSubmissionStatus::Failed)
        ->and($urlSubmission->error_message)->toBe('The page could not be downloaded.');
})->with([
    'server error' => [fn () => Http::response('Error', 500)],
    'connection error' => [fn () => Http::failedConnection()],
]);

it('fails when no article can be extracted', function (): void {
    Http::fake(['https://example.com/article' => Http::response('<html><body><p>Too short.</p></body></html>')]);

    $urlSubmission = UrlSubmission::factory()->create(['url' => 'https://example.com/article']);

    expect(resolve(ProcessUrlSubmission::class)->handle($urlSubmission))->toBeNull()
        ->and($urlSubmission->refresh()->status)->toBe(UrlSubmissionStatus::Failed)
        ->and($urlSubmission->error_message)->toBe('Could not extract enough article content from this page.')
        ->and($urlSubmission->submission_id)->toBeNull();
});
