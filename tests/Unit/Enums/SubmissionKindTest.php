<?php

declare(strict_types=1);

use App\Enums\SubmissionKind;

it('has a label for every kind', function (SubmissionKind $kind, string $label): void {
    expect($kind->label())->toBe($label);
})->with([
    [SubmissionKind::Argument, 'Argument'],
    [SubmissionKind::Informational, 'Informational text'],
    [SubmissionKind::Personal, 'Personal impression'],
]);
