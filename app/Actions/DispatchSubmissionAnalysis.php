<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\MapSubmissionJob;
use App\Models\Submission;
use Illuminate\Support\Facades\Bus;

final readonly class DispatchSubmissionAnalysis
{
    public function handle(Submission $submission): void
    {
        $submissionId = $submission->id;

        Bus::batch([new MapSubmissionJob($submission)])
            ->name('Analyze submission #'.$submissionId)
            ->allowFailures()
            ->finally(function () use ($submissionId): void {
                resolve(FinalizeSubmissionAnalysis::class)->handle(
                    Submission::query()->findOrFail($submissionId),
                );
            })
            ->dispatch();
    }
}
