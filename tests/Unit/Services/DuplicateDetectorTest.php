<?php

declare(strict_types=1);

use App\Enums\DuplicateMatch;
use App\Models\Submission;
use App\Services\DuplicateDetector;

/**
 * @return list<string>
 */
function duplicateWords(int $count, string $prefix = 'word'): array
{
    return array_map(fn (int $index): string => $prefix.$index, range(1, $count));
}

function createFingerprintedSubmission(string $body, ?string $source = null): Submission
{
    return Submission::factory()->create([
        'body' => $body,
        'source' => $source,
        ...resolve(DuplicateDetector::class)->fingerprint($body, $source),
    ]);
}

it('normalizes urls', function (string $url, string $expected): void {
    expect(resolve(DuplicateDetector::class)->normalizeUrl($url))->toBe($expected);
})->with([
    'strips www, tracking params and trailing slash' => ['https://www.Example.com/News/Article/?utm_source=x&b=2&a=1&fbclid=abc', 'https://example.com/News/Article?a=1&b=2'],
    'drops default ports' => ['http://example.com:80/path', 'http://example.com/path'],
    'drops https port' => ['https://example.com:443/path', 'https://example.com/path'],
    'keeps custom ports' => ['https://example.com:8080/path', 'https://example.com:8080/path'],
    'adds root path' => ['https://example.com', 'https://example.com/'],
    'drops query with only tracking params' => ['https://example.com/a?utm_medium=social&ref=home', 'https://example.com/a'],
    'defaults the scheme' => ['//example.com/a', 'https://example.com/a'],
    'returns urls without host as is' => ['not a url', 'not a url'],
    'returns unparsable urls as is' => ['http:///example.com', 'http:///example.com'],
]);

it('normalizes text', function (): void {
    $detector = resolve(DuplicateDetector::class);

    expect($detector->normalizeText("  Posted 5 minutes ago   HELLO\n\n World 120 views "))->toBe('posted hello world')
        ->and($detector->normalizeText('Just now Today 12/05/2024 2024.05.1 10:30 pm Text'))->toBe('text');
});

it('fingerprints a body and source', function (): void {
    $detector = resolve(DuplicateDetector::class);

    $fingerprint = $detector->fingerprint('Some  Body', 'https://www.example.com/a/');

    expect($fingerprint)->toBe([
        'normalized_url' => 'https://example.com/a',
        'url_hash' => hash('sha256', 'https://example.com/a'),
        'content_hash' => hash('sha256', 'some body'),
    ]);
});

it('ignores missing or invalid sources when fingerprinting', function (?string $source): void {
    $fingerprint = resolve(DuplicateDetector::class)->fingerprint('Body', $source);

    expect($fingerprint['normalized_url'])->toBeNull()
        ->and($fingerprint['url_hash'])->toBeNull();
})->with([null, 'not a url']);

it('finds submissions with the same url', function (): void {
    $submission = createFingerprintedSubmission('Body', 'https://example.com/article?id=1');
    createFingerprintedSubmission('Body', 'https://example.com/other');

    $matches = resolve(DuplicateDetector::class)->findByUrl('https://www.example.com/article/?id=1&utm_source=x');

    expect($matches)->toHaveCount(1)
        ->and($matches->first()['submission']->is($submission))->toBeTrue()
        ->and($matches->first()['similarity'])->toBe(1.0)
        ->and($matches->first()['match'])->toBe(DuplicateMatch::SameUrl);
});

it('finds exact text matches', function (): void {
    $submission = createFingerprintedSubmission('Exactly the same text');

    $matches = resolve(DuplicateDetector::class)->findByText("  exactly THE same\n text ");

    expect($matches)->toHaveCount(1)
        ->and($matches->first()['submission']->is($submission))->toBeTrue()
        ->and($matches->first()['similarity'])->toBe(1.0)
        ->and($matches->first()['match'])->toBe(DuplicateMatch::Exact);
});

it('does not compare short texts', function (): void {
    createFingerprintedSubmission(implode(' ', duplicateWords(5)).' extra');

    expect(resolve(DuplicateDetector::class)->findByText(implode(' ', duplicateWords(5))))->toBeEmpty();
});

it('classifies similar texts', function (string $existing, string $text, DuplicateMatch $match): void {
    $submission = createFingerprintedSubmission($existing);
    createFingerprintedSubmission(implode(' ', duplicateWords(30, 'other')));
    createFingerprintedSubmission('too short');

    $matches = resolve(DuplicateDetector::class)->findByText($text);

    expect($matches)->toHaveCount(1)
        ->and($matches->first()['submission']->is($submission))->toBeTrue()
        ->and($matches->first()['similarity'])->toBeGreaterThanOrEqual(0.7)
        ->and($matches->first()['match'])->toBe($match);
})->with([
    'near exact' => [
        implode(' ', duplicateWords(30)).'.',
        implode(', ', duplicateWords(30)),
        DuplicateMatch::NearExact,
    ],
    'subset of existing' => [
        implode(' ', duplicateWords(23)),
        implode(' ', duplicateWords(20)),
        DuplicateMatch::SubsetOfExisting,
    ],
    'contains existing' => [
        implode(' ', duplicateWords(20)),
        implode(' ', duplicateWords(23)),
        DuplicateMatch::ContainsExisting,
    ],
    'similar' => [
        implode(' ', duplicateWords(30)),
        str_replace('word15 ', 'changed ', implode(' ', duplicateWords(30))),
        DuplicateMatch::Similar,
    ],
]);

it('orders similar matches by similarity', function (): void {
    $text = implode(' ', duplicateWords(30));

    $closest = createFingerprintedSubmission(implode(' ', duplicateWords(31)));
    $further = createFingerprintedSubmission(implode(' ', duplicateWords(33)));

    $matches = resolve(DuplicateDetector::class)->findByText($text);

    expect($matches)->toHaveCount(2)
        ->and($matches[0]['submission']->is($closest))->toBeTrue()
        ->and($matches[1]['submission']->is($further))->toBeTrue();
});
