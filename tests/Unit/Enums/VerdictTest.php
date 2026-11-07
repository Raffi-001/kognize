<?php

declare(strict_types=1);

use App\Enums\Verdict;

it('has a label and a color for every verdict', function (Verdict $verdict, string $label, string $color): void {
    expect($verdict->getLabel())->toBe($label)
        ->and($verdict->getColor())->toBe($color);
})->with([
    [Verdict::Supported, 'Supported by evidence', 'success'],
    [Verdict::Refuted, 'Refuted by evidence', 'danger'],
    [Verdict::Contested, 'Contested', 'warning'],
]);
