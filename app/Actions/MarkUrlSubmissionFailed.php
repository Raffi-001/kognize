<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UrlSubmissionStatus;
use App\Models\UrlSubmission;

final readonly class MarkUrlSubmissionFailed
{
    public function handle(UrlSubmission $urlSubmission, string $message): void
    {
        $urlSubmission->update([
            'status' => UrlSubmissionStatus::Failed,
            'error_message' => $message,
        ]);
    }
}
