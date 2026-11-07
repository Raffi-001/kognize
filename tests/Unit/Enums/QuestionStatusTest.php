<?php

declare(strict_types=1);

use App\Enums\QuestionStatus;

it('has a label and a color for every status', function (QuestionStatus $status, string $label, string $color): void {
    expect($status->getLabel())->toBe($label)
        ->and($status->getColor())->toBe($color);
})->with([
    [QuestionStatus::Open, 'Open', 'warning'],
    [QuestionStatus::Resolved, 'Resolved', 'success'],
]);
