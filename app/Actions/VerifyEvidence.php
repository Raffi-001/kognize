<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EvidenceAssessment;
use App\Enums\EvidenceVerification;
use App\Models\Evidence;
use App\Services\OpenAi;
use App\Services\QuoteLocator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JsonException;

/**
 * Checks that a cited passage really is on the cited page, keeps a snapshot
 * of it so later edits cannot change the evidence, and asks the model
 * whether the passage supports or refutes the claim.
 */
final readonly class VerifyEvidence
{
    private const int CONTEXT = 300;

    public function __construct(
        private QuoteLocator $quotes,
        private OpenAi $openAi,
    ) {}

    public function handle(Evidence $evidence): Evidence
    {
        try {
            $html = Http::withHeaders(['Accept' => 'text/html,application/xhtml+xml'])
                ->timeout(20)
                ->get($evidence->url)
                ->throw()
                ->body();
        } catch (ConnectionException|RequestException) {
            $evidence->update(['verification' => EvidenceVerification::Unreachable]);

            return $evidence;
        }

        $text = $this->text($html);
        $location = $this->quotes->locate($text, $evidence->quote);
        $title = preg_match('/<title[^>]*>(.*?)<\/title>/isu', $html, $match) === 1
            ? Str::limit(mb_trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5)), 250)
            : null;

        if ($location === null) {
            $evidence->update(['verification' => EvidenceVerification::NotFound, 'page_title' => $title]);

            return $evidence;
        }

        $start = max(0, $location['offset'] - self::CONTEXT);
        $snapshot = mb_substr($text, $start, mb_strlen($location['text']) + 2 * self::CONTEXT);

        $evidence->update([
            'verification' => EvidenceVerification::Verified,
            'page_title' => $title,
            'snapshot' => $snapshot,
        ]);

        $this->assess($evidence, $snapshot);

        return $evidence;
    }

    private function assess(Evidence $evidence, string $snapshot): void
    {
        $evidence->loadMissing('question');

        try {
            $result = $this->openAi->structured(
                config()->string('analysis.evidence_prompt'),
                (string) json_encode([
                    'claim' => $evidence->question->label,
                    'question' => $evidence->question->aspect_label,
                    'excerpt' => $snapshot,
                ], JSON_UNESCAPED_UNICODE),
                OpenAi::schema('evidence_assessment', OpenAi::object([
                    'assessment' => OpenAi::enum(array_column(EvidenceAssessment::cases(), 'value')),
                    'reason' => ['type' => 'string'],
                ])),
            );
        } catch (ConnectionException|RequestException|JsonException $exception) {
            report($exception);

            return;
        }

        $reason = $result['reason'] ?? null;

        $evidence->update([
            'assessment' => EvidenceAssessment::tryFrom(is_string($result['assessment'] ?? null) ? $result['assessment'] : ''),
            'assessment_reason' => is_string($reason) && mb_trim($reason) !== '' ? mb_trim($reason) : null,
        ]);
    }

    private function text(string $html): string
    {
        $html = (string) preg_replace('/<(script|style|noscript|template)\b[^>]*>.*?<\/\1>/isu', ' ', $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);

        return mb_trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
