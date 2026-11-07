<?php

declare(strict_types=1);

namespace App\Services;

final readonly class QuoteLocator
{
    private const int MIN_LENGTH = 6;

    /**
     * Finds a quote in the text, tolerating surrounding quotation marks,
     * ellipses and whitespace differences. Returns the exact passage as it
     * appears in the text, with its character offset, or null when it is not there.
     *
     * @return array{offset: int, text: string}|null
     */
    public function locate(string $body, string $quote): ?array
    {
        $segments = preg_split('/\s*(?:…|\.{3})\s*/u', $quote) ?: [];

        $segments = array_map(
            fn (string $segment): string => (string) preg_replace('/^[\s«»"“”„\'‹›]+|[\s«»"“”„\'‹›.,։:;!?]+$/u', '', $segment),
            $segments,
        );

        usort($segments, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        foreach ($segments as $segment) {
            if (mb_strlen($segment) < self::MIN_LENGTH) {
                continue;
            }

            $words = preg_split('/\s+/u', $segment) ?: [];
            $pattern = '/'.implode('\s+', array_map(fn (string $word): string => preg_quote($word, '/'), $words)).'/u';

            if (preg_match($pattern, $body, $match) === 1) {
                return ['offset' => (int) mb_strpos($body, $match[0]), 'text' => $match[0]];
            }
        }

        return null;
    }
}
