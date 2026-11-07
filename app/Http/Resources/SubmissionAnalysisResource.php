<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SubmissionAnalysis;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SubmissionAnalysis
 */
final class SubmissionAnalysisResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'label' => $this->type->label(),
            'status' => $this->status,
            'notes' => AnalysisNoteResource::collection($this->notes),
        ];
    }
}
