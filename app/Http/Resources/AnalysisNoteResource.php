<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AnalysisNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AnalysisNote
 */
final class AnalysisNoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'annotation_id' => $this->annotation_id,
            'label' => $this->label,
            'aspect_label' => $this->aspect_label,
            'text' => $this->text,
            'ethics' => $this->ethics,
            'strength' => $this->strength,
            'comments' => CommentResource::collection($this->comments),
            'question_kind' => $this->question_kind,
            'status' => $this->status,
            'verdict' => $this->verdict,
            'verdict_label' => $this->verdict?->label(),
            'resolution' => $this->resolution,
            'resolved_at_human' => $this->resolved_at?->diffForHumans(),
            'evidence' => EvidenceResource::collection($this->whenLoaded('evidence')),
        ];
    }
}
