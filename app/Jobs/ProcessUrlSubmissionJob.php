<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\MarkUrlSubmissionFailed;
use App\Actions\ProcessUrlSubmission;
use App\Models\UrlSubmission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

#[Timeout(120)]
#[Tries(1)]
final class ProcessUrlSubmissionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly UrlSubmission $urlSubmission) {}

    public function handle(ProcessUrlSubmission $action): void
    {
        $action->handle($this->urlSubmission);
    }

    public function failed(): void
    {
        resolve(MarkUrlSubmissionFailed::class)->handle($this->urlSubmission, 'Something went wrong while processing this URL.');
    }
}
