<?php

declare(strict_types=1);

use App\Models\Submission;
use App\Models\User;
use App\Services\DuplicateDetector;
use Illuminate\Support\Str;

it('requires authentication', function (): void {
    $this->postJson(route('submission-duplicate-checks.store'), ['url' => 'https://example.com'])
        ->assertUnauthorized();
});

it('finds duplicates by url', function (): void {
    $submission = Submission::factory()->create([
        'title' => 'Existing',
        'body' => "Some   existing\nbody",
        'source' => 'https://example.com/article',
        ...resolve(DuplicateDetector::class)->fingerprint('Body', 'https://example.com/article'),
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->postJson(route('submission-duplicate-checks.store'), ['url' => 'https://www.example.com/article/?utm_source=x']);

    $response->assertOk()
        ->assertExactJson([
            'duplicates' => [
                [
                    'id' => $submission->id,
                    'title' => 'Existing',
                    'source' => 'https://example.com/article',
                    'preview' => 'Some existing body',
                    'similarity' => 1,
                    'match' => 'same_url',
                    'match_label' => 'Same source URL',
                    'created_at_human' => '0 seconds ago',
                ],
            ],
        ]);
});

it('finds duplicates by text', function (): void {
    $body = config()->string('analysis.sample_text');

    $submission = Submission::factory()->create([
        'body' => $body,
        ...resolve(DuplicateDetector::class)->fingerprint($body),
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->postJson(route('submission-duplicate-checks.store'), ['text' => $body]);

    $response->assertOk()
        ->assertJsonCount(1, 'duplicates')
        ->assertJsonPath('duplicates.0.id', $submission->id)
        ->assertJsonPath('duplicates.0.match', 'exact')
        ->assertJsonPath('duplicates.0.match_label', 'Exact match')
        ->assertJsonPath('duplicates.0.preview', Str::limit($body, 200));
});

it('returns no duplicates when nothing matches', function (): void {
    $response = $this->actingAs(User::factory()->create())
        ->postJson(route('submission-duplicate-checks.store'), ['text' => config('analysis.sample_text')]);

    $response->assertOk()
        ->assertExactJson(['duplicates' => []]);
});

it('validates the request', function (array $data, string $error): void {
    $this->actingAs(User::factory()->create())
        ->postJson(route('submission-duplicate-checks.store'), $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($error);
})->with([
    'missing both' => [[], 'url'],
    'invalid url' => [['url' => 'not a url'], 'url'],
    'short text' => [['text' => 'Too short'], 'text'],
]);
