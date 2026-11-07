<?php

declare(strict_types=1);

use App\Services\OpenAi;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

it('builds strict json schemas', function (): void {
    $text = ['type' => 'string'];

    expect(OpenAi::schema('answer', OpenAi::object(['name' => $text])))->toBe([
        'type' => 'json_schema',
        'json_schema' => [
            'name' => 'answer',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['name'],
                'properties' => ['name' => $text],
            ],
        ],
    ])
        ->and(OpenAi::nullable($text))->toBe(['anyOf' => [['type' => 'null'], $text]])
        ->and(OpenAi::enum(['a', 'b']))->toBe(['type' => 'string', 'enum' => ['a', 'b']])
        ->and(OpenAi::list($text))->toBe(['type' => 'array', 'items' => $text]);
});

it('asks the model for a structured answer', function (): void {
    fakeOpenAi(['answer' => 'Այո']);

    $format = OpenAi::schema('answer', OpenAi::object(['answer' => ['type' => 'string']]));

    $result = resolve(OpenAi::class)->structured('Instructions', 'Input', $format);

    expect($result)->toBe(['answer' => 'Այո']);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openai.com/v1/chat/completions'
        && $request->hasHeader('Authorization', 'Bearer test-key')
        && $request['model'] === config('analysis.openai.model')
        && $request['temperature'] === config('analysis.openai.temperature')
        && $request['response_format'] === $format
        && $request['messages'] === [
            ['role' => 'system', 'content' => 'Instructions'],
            ['role' => 'user', 'content' => 'Input'],
        ]);
});

it('throws when the request or the answer fails', function (Closure $fake, string $exception): void {
    config(['services.openai.key' => 'test-key', 'services.openai.url' => 'https://api.openai.com/v1']);

    $fake();

    expect(fn (): array => resolve(OpenAi::class)->structured('Instructions', 'Input', []))->toThrow($exception);
})->with([
    'connection error' => [fn () => Http::fake(['*' => Http::failedConnection()]), ConnectionException::class],
    'server error' => [fn () => Http::fake(['*' => Http::response('Error', 500)]), RequestException::class],
    'invalid json' => [fn () => fakeOpenAi('{invalid'), JsonException::class],
    'non object json' => [fn () => fakeOpenAi('"just a string"'), JsonException::class],
    'missing content' => [fn () => Http::fake(['*' => Http::response(['choices' => []])]), JsonException::class],
]);
