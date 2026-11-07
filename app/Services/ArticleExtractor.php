<?php

declare(strict_types=1);

namespace App\Services;

use fivefilters\Readability\Readability;
use Illuminate\Support\Facades\Http;

final readonly class ArticleExtractor
{
    private const int MIN_LENGTH = 200;

    /**
     * @return array{title: string|null, body: string}|null
     */
    public function extract(string $url): ?array
    {
        $html = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml',
            'Accept-Language' => 'hy,en-US;q=0.9,en;q=0.8',
        ])->timeout(20)->get($url)->throw()->body();

        if (mb_trim($html) === '') {
            return null;
        }

        $article = new Readability()->parse($html);

        $body = $this->normalize((string) $article->textContent);

        if (mb_strlen($body) < self::MIN_LENGTH) {
            return null;
        }

        $title = mb_trim($article->title);

        return [
            'title' => $title === '' ? null : $title,
            'body' => $body,
        ];
    }

    private function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = (string) preg_replace("/[ \t\x{00A0}]+/u", ' ', $text);
        $text = (string) preg_replace("/ *\n */", "\n", $text);
        $text = (string) preg_replace("/\n{3,}/", "\n\n", $text);

        return mb_trim($text);
    }
}
