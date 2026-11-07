<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DuplicateMatch;
use App\Http\Requests\CreateSubmissionDuplicateCheckRequest;
use App\Models\Submission;
use App\Services\DuplicateDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final readonly class SubmissionDuplicateCheckController
{
    public function store(CreateSubmissionDuplicateCheckRequest $request, DuplicateDetector $detector): JsonResponse
    {
        $matches = $request->filled('url')
            ? $detector->findByUrl($request->string('url')->value())
            : $detector->findByText($request->string('text')->value());

        return response()->json([
            'duplicates' => $matches->map(fn (array $match): array => $this->present(...$match))->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Submission $submission, float $similarity, DuplicateMatch $match): array
    {
        return [
            'id' => $submission->id,
            'title' => $submission->title,
            'source' => $submission->source,
            'preview' => Str::limit(Str::squish($submission->body), 200),
            'similarity' => $similarity,
            'match' => $match,
            'match_label' => $match->label(),
            'created_at_human' => $submission->created_at->diffForHumans(),
        ];
    }
}
