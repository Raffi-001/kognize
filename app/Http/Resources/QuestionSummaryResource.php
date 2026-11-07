<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AnalysisNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AnalysisNote
 */
final class QuestionSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->aspect_label ?? $this->text,
            'passage' => $this->label,
            'status' => $this->status,
            'verdict' => $this->verdict,
            'verdict_label' => $this->verdict?->label(),
            'evidence_count' => $this->evidence_count,
            'comments_count' => $this->comments_count,
            'source' => SubmissionSourceResource::make($this->analysis->submission),
            'created_at_human' => $this->created_at->diffForHumans(),
            'resolved_at_human' => $this->resolved_at?->diffForHumans(),
        ];
    }
}
