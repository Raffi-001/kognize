<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\NotificationKind;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * @mixin DatabaseNotification
 */
final class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        assert($this->resource instanceof DatabaseNotification);

        return [
            'id' => $this->id,
            ...NotificationKind::summarize($this->resource),
            'is_read' => $this->read_at !== null,
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
