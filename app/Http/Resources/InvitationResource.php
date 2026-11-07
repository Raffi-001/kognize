<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invitation
 */
final class InvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'code' => $this->code,
            'is_used' => $this->used_at !== null,
            'invitee' => $this->invitee === null ? null : UserSummaryResource::make($this->invitee),
            'used_at_human' => $this->used_at?->diffForHumans(),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}
