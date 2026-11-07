<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DuplicateMatch;
use App\Models\Submission;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class DuplicateDetector
{
    private const float SIMILARITY_THRESHOLD = 0.7;

    private const int MIN_WORDS_FOR_COMPARISON = 10;

    private const int CANDIDATES = 100;

    /**
     * @var list<string>
     */
    private const array TRACKING_PARAMETERS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid', 'ref', 'source'];

    /**
     * @var list<string>
     */
    private const array DYNAMIC_CONTENT_PATTERNS = [
        '/\d+\s*(second|minute|hour|day|week|month|year)s?\s*ago/i',
        '/just\s+now/i',
        '/today|yesterday/i',
        '/\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}/',
        '/\d{4}[\/\-\.]\d{1,2}[\/\-\.]\d{1,2}/',
        '/\d{1,2}:\d{2}(:\d{2})?\s*(am|pm)?/i',
        '/\d+\s*(views?|likes?|comments?|shares?|followers?)/i',
    ];

    /**
     * @return Collection<int, array{submission: Submission, similarity: float, match: DuplicateMatch}>
     */
    public function findByUrl(string $url): Collection
    {
        return Submission::query()
            ->where('url_hash', $this->hash($this->normalizeUrl($url)))
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Submission $submission): array => $this->match($submission, 1.0, DuplicateMatch::SameUrl));
    }

    /**
     * @return Collection<int, array{submission: Submission, similarity: float, match: DuplicateMatch}>
     */
    public function findByText(string $text): Collection
    {
        $normalized = $this->normalizeText($text);

        $exact = Submission::query()
            ->where('content_hash', $this->hash($normalized))
            ->latest()
            ->limit(5)
            ->get();

        if ($exact->isNotEmpty()) {
            return $exact->map(fn (Submission $submission): array => $this->match($submission, 1.0, DuplicateMatch::Exact));
        }

        return $this->findSimilar($normalized);
    }

    /**
     * @return array{normalized_url: string|null, url_hash: string|null, content_hash: string}
     */
    public function fingerprint(string $body, ?string $source = null): array
    {
        $normalizedUrl = $source !== null && filter_var($source, FILTER_VALIDATE_URL) !== false
            ? $this->normalizeUrl($source)
            : null;

        return [
            'normalized_url' => $normalizedUrl,
            'url_hash' => $normalizedUrl === null ? null : $this->hash($normalizedUrl),
            'content_hash' => $this->hash($this->normalizeText($body)),
        ];
    }

    public function normalizeUrl(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return $url;
        }

        $normalized = mb_strtolower($parts['scheme'] ?? 'https').'://';
        $normalized .= (string) preg_replace('/^www\./', '', mb_strtolower($parts['host']));

        if (isset($parts['port']) && ! in_array($parts['port'], [80, 443], true)) {
            $normalized .= ':'.$parts['port'];
        }

        $path = mb_rtrim($parts['path'] ?? '', '/');
        $normalized .= $path === '' ? '/' : $path;

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);

            $query = array_diff_key($query, array_flip(self::TRACKING_PARAMETERS));

            if ($query !== []) {
                ksort($query);
                $normalized .= '?'.http_build_query($query);
            }
        }

        return $normalized;
    }

    public function normalizeText(string $text): string
    {
        $normalized = (string) preg_replace(self::DYNAMIC_CONTENT_PATTERNS, '', $text);
        $normalized = (string) preg_replace('/\s+/u', ' ', $normalized);

        return mb_strtolower(mb_trim($normalized));
    }

    /**
     * @return Collection<int, array{submission: Submission, similarity: float, match: DuplicateMatch}>
     */
    private function findSimilar(string $normalized): Collection
    {
        $words = $this->words($normalized);

        if (count($words) < self::MIN_WORDS_FOR_COMPARISON) {
            return new Collection;
        }

        $shingles = $this->shingles($words);

        return Submission::query()
            ->latest()
            ->limit(self::CANDIDATES)
            ->get()
            ->map(function (Submission $candidate) use ($normalized, $shingles): ?array {
                $candidateText = $this->normalizeText($candidate->body);
                $similarity = $this->jaccard($shingles, $this->shingles($this->words($candidateText)));

                if ($similarity < self::SIMILARITY_THRESHOLD) {
                    return null;
                }

                return $this->match($candidate, round($similarity, 2), $this->classify($normalized, $candidateText, $similarity));
            })
            ->filter()
            ->sortByDesc('similarity')
            ->take(5)
            ->values();
    }

    /**
     * @return list<string>
     */
    private function words(string $text): array
    {
        preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);

        return $matches[0];
    }

    /**
     * @param  list<string>  $words
     * @return list<string>
     */
    private function shingles(array $words, int $size = 3): array
    {
        $shingles = [];

        for ($i = 0; $i <= count($words) - $size; $i++) {
            $shingles[] = implode(' ', array_slice($words, $i, $size));
        }

        return array_values(array_unique($shingles));
    }

    /**
     * @param  list<string>  $first
     * @param  list<string>  $second
     */
    private function jaccard(array $first, array $second): float
    {
        if ($first === [] || $second === []) {
            return 0.0;
        }

        $intersection = count(array_intersect($first, $second));
        $union = count(array_unique([...$first, ...$second]));

        return $intersection / $union;
    }

    private function classify(string $text, string $candidate, float $similarity): DuplicateMatch
    {
        return match (true) {
            $similarity >= 0.95 => DuplicateMatch::NearExact,
            Str::contains($candidate, $text) => DuplicateMatch::SubsetOfExisting,
            Str::contains($text, $candidate) => DuplicateMatch::ContainsExisting,
            default => DuplicateMatch::Similar,
        };
    }

    /**
     * @return array{submission: Submission, similarity: float, match: DuplicateMatch}
     */
    private function match(Submission $submission, float $similarity, DuplicateMatch $match): array
    {
        return [
            'submission' => $submission,
            'similarity' => $similarity,
            'match' => $match,
        ];
    }

    private function hash(string $value): string
    {
        return hash('sha256', $value);
    }
}
