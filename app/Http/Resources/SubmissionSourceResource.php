<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * The article a question was raised by, as a link back to it.
 *
 * @mixin Submission
 */
final class SubmissionSourceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $host = $this->source === null ? null : parse_url($this->source, PHP_URL_HOST);

        return [
            'id' => $this->id,
            'title' => $this->title ?? Str::words(Str::squish($this->body), 14),
            'source_host' => is_string($host) ? Str::after($host, 'www.') : null,
        ];
    }
}
