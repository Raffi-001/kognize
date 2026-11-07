<?php

declare(strict_types=1);

use App\Enums\EvidenceVerification;

it('has a label and a color for every verification', function (EvidenceVerification $verification, string $label, string $color): void {
    expect($verification->getLabel())->toBe($label)
        ->and($verification->getColor())->toBe($color);
})->with([
    [EvidenceVerification::Pending, 'Checking', 'gray'],
    [EvidenceVerification::Verified, 'Quote found', 'success'],
    [EvidenceVerification::NotFound, 'Quote not found', 'danger'],
    [EvidenceVerification::Unreachable, 'Page unreachable', 'danger'],
]);
