<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SubmissionState;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;

/**
 * Runs the analysis of a failed submission again from scratch. Only failed
 * submissions qualify, so no findings, evidence or discussion are lost.
 */
final readonly class RetrySubmissionAnalysis
{
    public function __construct(private DispatchSubmissionAnalysis $dispatchAnalysis) {}

    public function handle(Submission $submission): void
    {
        abort_unless($submission->state === SubmissionState::Failed, 422, 'Only failed submissions can be retried.');

        DB::transaction(function () use ($submission): void {
            $submission->analyses()->delete();
            $submission->update(['state' => SubmissionState::Pending, 'kind' => null, 'argument_map' => null]);
        });

        $this->dispatchAnalysis->handle($submission);
    }
}
