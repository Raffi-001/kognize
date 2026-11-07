<?php

declare(strict_types=1);

use App\Actions\VerifyEvidence;
use App\Enums\EvidenceAssessment;
use App\Enums\EvidenceVerification;
use App\Models\AnalysisNote;
use App\Models\Evidence;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;

$quote = 'Բյուջեի ծախսերն աճել են տասը տոկոսով';

/**
 * @param  array<string, mixed>  $attributes
 */
function citedEvidence(string $quote, array $attributes = []): Evidence
{
    $question = AnalysisNote::factory()->question()->create([
        'label' => 'Ծախսերն աճել են',
        'aspect_label' => 'Արդյո՞ք ծախսերն աճել են։',
    ]);

    return Evidence::factory()->for($question, 'question')->create([
        'url' => 'https://example.com/report',
        'quote' => $quote,
        ...$attributes,
    ]);
}

it('marks the evidence as unreachable when the page cannot be downloaded', function (Closure $response) use ($quote): void {
    Http::fake(['https://example.com/report' => $response()]);

    $evidence = resolve(VerifyEvidence::class)->handle(citedEvidence($quote));

    expect($evidence->refresh()->verification)->toBe(EvidenceVerification::Unreachable)
        ->and($evidence->snapshot)->toBeNull();

    Http::assertSentCount(1);
})->with([
    'server error' => [fn () => Http::response('Error', 500)],
    'connection error' => [fn () => Http::failedConnection()],
]);

it('marks the evidence as not found when the passage is not on the page', function (string $html, ?string $title) use ($quote): void {
    Http::fake(['https://example.com/report' => Http::response($html)]);

    $evidence = resolve(VerifyEvidence::class)->handle(citedEvidence($quote));

    expect($evidence->refresh()->verification)->toBe(EvidenceVerification::NotFound)
        ->and($evidence->page_title)->toBe($title)
        ->and($evidence->snapshot)->toBeNull()
        ->and($evidence->assessment)->toBeNull();

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Accept', 'text/html,application/xhtml+xml'));
    Http::assertSentCount(1);
})->with([
    'other text' => ['<html><head><title> Զեկույց &amp; տվյալներ </title></head><body><p>Այլ տեքստ</p></body></html>', 'Զեկույց & տվյալներ'],
    'only in scripts and styles' => ["<html><body><script>var a = '{$quote}';</script><style>/* {$quote} */</style><p>Այլ տեքստ</p></body></html>", null],
]);

it('keeps a snapshot of the verified passage and stores the assessment', function () use ($quote): void {
    $before = str_repeat('Ա', 400);
    $after = str_repeat('Բ', 400);

    fakeOpenAi(['assessment' => 'refutes', 'reason' => '  Թվերը տարբեր են։  ']);
    Http::fake(['https://example.com/report' => Http::response("<html><head><title>Զեկույց</title></head><body><p>{$before}</p>\n<p>Բյուջեի   ծախսերն\nաճել են տասը տոկոսով</p><p>{$after}</p></body></html>")]);

    $evidence = resolve(VerifyEvidence::class)->handle(citedEvidence($quote));

    expect($evidence->refresh()->verification)->toBe(EvidenceVerification::Verified)
        ->and($evidence->page_title)->toBe('Զեկույց')
        ->and($evidence->snapshot)->toBe(str_repeat('Ա', 299).' Բյուջեի ծախսերն աճել են տասը տոկոսով'.str_repeat('Բ', 300))
        ->and($evidence->assessment)->toBe(EvidenceAssessment::Refutes)
        ->and($evidence->assessment_reason)->toBe('Թվերը տարբեր են։');

    Http::assertSent(function (Request $request) use ($evidence): bool {
        if ($request->url() !== 'https://api.openai.com/v1/chat/completions') {
            return false;
        }

        return $request['messages'][0]['content'] === config('analysis.evidence_prompt')
            && json_decode($request['messages'][1]['content'], true) === [
                'claim' => 'Ծախսերն աճել են',
                'question' => 'Արդյո՞ք ծախսերն աճել են։',
                'excerpt' => $evidence->snapshot,
            ]
            && $request['response_format']['json_schema']['name'] === 'evidence_assessment'
            && $request['response_format']['json_schema']['schema']['properties']['assessment']['enum'] === ['supports', 'refutes', 'irrelevant'];
    });
});

it('ignores an assessment it does not understand', function (array $answer) use ($quote): void {
    fakeOpenAi($answer);
    Http::fake(['https://example.com/report' => Http::response("<p>{$quote}</p>")]);

    $evidence = resolve(VerifyEvidence::class)->handle(citedEvidence($quote));

    expect($evidence->refresh()->verification)->toBe(EvidenceVerification::Verified)
        ->and($evidence->snapshot)->toBe($quote)
        ->and($evidence->page_title)->toBeNull()
        ->and($evidence->assessment)->toBeNull()
        ->and($evidence->assessment_reason)->toBeNull();
})->with([
    'unknown values' => [['assessment' => 'maybe', 'reason' => ' ']],
    'wrong types' => [['assessment' => 1, 'reason' => ['no']]],
]);

it('leaves the assessment empty when the model request fails', function () use ($quote): void {
    Exceptions::fake();

    config(['services.openai.key' => 'test-key', 'services.openai.url' => 'https://api.openai.com/v1']);

    Http::fake([
        'https://example.com/report' => Http::response("<p>{$quote}</p>"),
        'https://api.openai.com/*' => Http::response('Error', 500),
    ]);

    $evidence = resolve(VerifyEvidence::class)->handle(citedEvidence($quote));

    expect($evidence->refresh()->verification)->toBe(EvidenceVerification::Verified)
        ->and($evidence->assessment)->toBeNull();

    Exceptions::assertReported(RequestException::class);
});
