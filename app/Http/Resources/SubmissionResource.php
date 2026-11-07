<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\AnalysisType;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use App\Rules\ArmenianText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Submission
 */
final class SubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $counts = [];

        foreach (AnalysisType::cases() as $type) {
            $analysis = $this->analyses->first(fn (SubmissionAnalysis $analysis): bool => $analysis->type === $type);
            $count = $analysis?->getAttribute('notes_count');
            $counts[$type->value] = is_numeric($count) ? (int) $count : 0;
        }

        $host = $this->source === null ? null : parse_url($this->source, PHP_URL_HOST);

        return [
            'id' => $this->id,
            'title' => $this->title ?? Str::words(Str::squish($this->body), 14),
            'excerpt' => Str::limit(Str::squish($this->body), 220),
            'source' => $this->source,
            'source_host' => is_string($host) ? Str::after($host, 'www.') : null,
            'state' => $this->state,
            'state_label' => $this->state->label(),
            'kind' => $this->kind,
            'kind_label' => $this->kind?->label(),
            'is_in_progress' => $this->state->isInProgress(),
            'counts' => $counts,
            'word_count' => ArmenianText::wordCount($this->body),
            'author' => UserSummaryResource::make($this->user),
            'is_owner' => $request->user()?->getAuthIdentifier() === $this->user_id,
            'created_at' => $this->created_at->toIso8601String(),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}
