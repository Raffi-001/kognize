<?php

declare(strict_types=1);

use App\Enums\QuestionKind;

it('has a label and a color for every kind', function (QuestionKind $kind, string $label, string $color): void {
    expect($kind->getLabel())->toBe($label)
        ->and($kind->getColor())->toBe($color);
})->with([
    [QuestionKind::Factual, 'Factual', 'info'],
    [QuestionKind::Value, 'Value', 'primary'],
]);
