<?php

declare(strict_types=1);

use App\Enums\PollStatus;

it('has a label and a color for every status', function (PollStatus $status, string $label, string $color): void {
    expect($status->getLabel())->toBe($label)
        ->and($status->getColor())->toBe($color);
})->with([
    [PollStatus::Draft, 'In review', 'warning'],
    [PollStatus::Published, 'Published', 'success'],
    [PollStatus::Closed, 'Closed', 'gray'],
]);
