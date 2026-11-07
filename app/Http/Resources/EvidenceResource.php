<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Evidence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Evidence
 */
final class EvidenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $host = parse_url($this->url, PHP_URL_HOST);

        return [
            'id' => $this->id,
            'stance' => $this->stance,
            'url' => $this->url,
            'source_host' => is_string($host) ? Str::after($host, 'www.') : $this->url,
            'page_title' => $this->page_title,
            'quote' => $this->quote,
            'explanation' => $this->explanation,
            'verification' => $this->verification,
            'assessment' => $this->assessment,
            'assessment_reason' => $this->assessment_reason,
            'author' => UserSummaryResource::make($this->user),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}
