<?php

declare(strict_types=1);

use App\Enums\UrlSubmissionStatus;

it('knows when a status is in progress', function (UrlSubmissionStatus $status, bool $inProgress): void {
    expect($status->isInProgress())->toBe($inProgress);
})->with([
    [UrlSubmissionStatus::Pending, true],
    [UrlSubmissionStatus::Processing, true],
    [UrlSubmissionStatus::Completed, false],
    [UrlSubmissionStatus::Failed, false],
]);
