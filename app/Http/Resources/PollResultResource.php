<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A poll with its answer counts. Expects options loaded with a votes count.
 *
 * @mixin Poll
 */
final class PollResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $total = (int) $this->options->sum(fn (PollOption $option): int => $this->votes($option));

        return [
            'id' => $this->id,
            'question' => $this->question,
            'status' => $this->status,
            'total_votes' => $total,
            'options' => $this->options->map(fn (PollOption $option): array => [
                'id' => $option->id,
                'label' => $option->label,
                'votes' => $this->votes($option),
                'share' => $total > 0 ? round($this->votes($option) / $total * 100) : 0,
            ])->values(),
        ];
    }

    private function votes(PollOption $option): int
    {
        $count = $option->getAttribute('votes_count');

        return is_numeric($count) ? (int) $count : 0;
    }
}
