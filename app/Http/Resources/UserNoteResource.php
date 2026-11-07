<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UserNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserNote
 */
final class UserNoteResource extends JsonResource
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
            'text' => $this->text,
            'author' => UserSummaryResource::make($this->user),
            'is_owner' => $request->user()?->getAuthIdentifier() === $this->user_id,
            'created_at_human' => $this->created_at->diffForHumans(),
            'comments' => CommentResource::collection($this->comments),
        ];
    }
}
