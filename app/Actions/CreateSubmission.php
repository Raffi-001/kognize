<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Submission;
use App\Models\User;
use App\Services\DuplicateDetector;
use Illuminate\Support\Str;

final readonly class CreateSubmission
{
    public function __construct(
        private DuplicateDetector $duplicates,
        private DispatchSubmissionAnalysis $dispatchAnalysis,
    ) {}

    public function handle(User $user, string $body, ?string $source = null, ?string $title = null): Submission
    {
        $body = mb_trim($body);

        $submission = $user->submissions()->create([
            'title' => $title ?? Str::words(Str::squish($body), 14),
            'body' => $body,
            'source' => $source,
            ...$this->duplicates->fingerprint($body, $source),
        ]);

        $this->dispatchAnalysis->handle($submission);

        return $submission;
    }
}
