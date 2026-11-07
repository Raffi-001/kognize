<?php

declare(strict_types=1);

use App\Services\ArticleExtractor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

function articleHtml(string $title, string $paragraph): string
{
    return '<!DOCTYPE html><html><head><title>'.$title.'</title></head><body>'
        .'<nav>Home | About</nav>'
        .'<article><h1>'.$title.'</h1><p>'.$paragraph.'</p><p>'.$paragraph.'</p></article>'
        .'</body></html>';
}

it('extracts the article title and body', function (): void {
    $paragraph = str_repeat("Սա   հոդվածի\t բովանդակությունն է, որը բավականաչափ երկար է։ ", 6);

    Http::fake([
        'https://example.com/article' => Http::response(articleHtml('Հոդվածի վերնագիր', $paragraph)),
    ]);

    $article = resolve(ArticleExtractor::class)->extract('https://example.com/article');

    expect($article)->not->toBeNull()
        ->and($article['title'])->toBe('Հոդվածի վերնագիր')
        ->and($article['body'])->toContain('Սա հոդվածի բովանդակությունն է')
        ->and($article['body'])->not->toContain('  ')
        ->and(mb_strlen($article['body']))->toBeGreaterThanOrEqual(200);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://example.com/article'
        && $request->hasHeader('Accept-Language', 'hy,en-US;q=0.9,en;q=0.8'));
});

it('returns a null title when the page has none', function (): void {
    $paragraph = str_repeat('This is the article content and it is long enough to be extracted. ', 6);

    Http::fake([
        'https://example.com/article' => Http::response('<html><body><article><p>'.$paragraph."</p>\r\n\r\n\r\n\r\n<p>".$paragraph.'</p></article></body></html>'),
    ]);

    $article = resolve(ArticleExtractor::class)->extract('https://example.com/article');

    expect($article)->not->toBeNull()
        ->and($article['title'])->toBeNull()
        ->and($article['body'])->not->toContain("\n\n\n");
});

it('returns null for empty pages', function (): void {
    Http::fake([
        'https://example.com/article' => Http::response('   '),
    ]);

    expect(resolve(ArticleExtractor::class)->extract('https://example.com/article'))->toBeNull();
});

it('returns null when the article is too short', function (): void {
    Http::fake([
        'https://example.com/article' => Http::response(articleHtml('Title', 'Too short.')),
    ]);

    expect(resolve(ArticleExtractor::class)->extract('https://example.com/article'))->toBeNull();
});

it('throws when the page cannot be downloaded', function (): void {
    Http::fake([
        'https://example.com/article' => Http::response('Not Found', 404),
    ]);

    resolve(ArticleExtractor::class)->extract('https://example.com/article');
})->throws(RequestException::class);

it('throws when the connection fails', function (): void {
    Http::fake([
        'https://example.com/article' => Http::failedConnection(),
    ]);

    resolve(ArticleExtractor::class)->extract('https://example.com/article');
})->throws(ConnectionException::class);
