<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Comment
 */
final class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'author' => UserSummaryResource::make($this->user),
            'score' => $this->score,
            'my_vote' => $this->voteOf($request->user()?->getAuthIdentifier()),
            'is_owner' => $request->user()?->getAuthIdentifier() === $this->user_id,
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}
