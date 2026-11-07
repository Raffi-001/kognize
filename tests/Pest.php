<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

/**
 * Fakes the OpenAI chat completions endpoint so it answers with the given message content.
 *
 * @param  array<string, mixed>|string  $content
 */
function fakeOpenAi(array|string $content): void
{
    config([
        'services.openai.key' => 'test-key',
        'services.openai.url' => 'https://api.openai.com/v1',
    ]);

    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => is_string($content) ? $content : json_encode($content)]],
            ],
        ]),
    ]);
}

function something(): void
{
    // ..
}
