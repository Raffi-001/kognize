<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\MapSubmissionArgument;
use App\Actions\MarkSubmissionAnalysisFailed;
use App\Models\Submission;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

#[Timeout(600)]
#[Tries(1)]
final class MapSubmissionJob implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public function __construct(public readonly Submission $submission) {}

    public function handle(MapSubmissionArgument $action): void
    {
        $action->handle($this->submission);
    }

    public function failed(): void
    {
        resolve(MarkSubmissionAnalysisFailed::class)->handle($this->submission);
    }
}
