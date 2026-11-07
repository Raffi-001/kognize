<?php

declare(strict_types=1);

use App\Enums\Stance;

it('has a label and a color for every stance', function (Stance $stance, string $label, string $color): void {
    expect($stance->getLabel())->toBe($label)
        ->and($stance->getColor())->toBe($color);
})->with([
    [Stance::Supports, 'Supports the claim', 'success'],
    [Stance::Refutes, 'Refutes the claim', 'danger'],
]);
