<?php

declare(strict_types=1);

use App\Actions\CreateSubmission;
use App\Enums\SubmissionState;
use App\Models\User;
use App\Services\DuplicateDetector;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

it('creates a submission and dispatches its analysis', function (): void {
    Bus::fake();

    $user = User::factory()->create();
    $body = config()->string('analysis.sample_text');

    $submission = resolve(CreateSubmission::class)->handle($user, "  {$body}  ", 'https://www.example.com/article/');

    expect($submission->user->is($user))->toBeTrue()
        ->and($submission->body)->toBe($body)
        ->and($submission->title)->toBe(Str::words($body, 14))
        ->and($submission->source)->toBe('https://www.example.com/article/')
        ->and($submission->normalized_url)->toBe('https://example.com/article')
        ->and($submission->url_hash)->toBe(hash('sha256', 'https://example.com/article'))
        ->and($submission->content_hash)->toBe(hash('sha256', resolve(DuplicateDetector::class)->normalizeText($body)))
        ->and($submission->refresh()->state)->toBe(SubmissionState::Pending);

    Bus::assertBatched(fn (PendingBatch $batch): bool => $batch->name === 'Analyze submission #'.$submission->id);
});

it('uses the given title', function (): void {
    Bus::fake();

    $submission = resolve(CreateSubmission::class)->handle(
        User::factory()->create(),
        config()->string('analysis.sample_text'),
        null,
        'Custom title',
    );

    expect($submission->title)->toBe('Custom title')
        ->and($submission->source)->toBeNull()
        ->and($submission->normalized_url)->toBeNull();
});
