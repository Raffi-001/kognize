<?php

declare(strict_types=1);

use App\Enums\SubmissionState;

it('has a label for every state', function (SubmissionState $state, string $label): void {
    expect($state->label())->toBe($label);
})->with([
    [SubmissionState::Pending, 'Queued'],
    [SubmissionState::Processing, 'Analyzing'],
    [SubmissionState::Analyzed, 'Analyzed'],
    [SubmissionState::Failed, 'Failed'],
]);

it('knows when a state is in progress', function (SubmissionState $state, bool $inProgress): void {
    expect($state->isInProgress())->toBe($inProgress);
})->with([
    [SubmissionState::Pending, true],
    [SubmissionState::Processing, true],
    [SubmissionState::Analyzed, false],
    [SubmissionState::Failed, false],
]);
