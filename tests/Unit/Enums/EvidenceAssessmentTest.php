<?php

declare(strict_types=1);

use App\Enums\EvidenceAssessment;

it('has a label and a color for every assessment', function (EvidenceAssessment $assessment, string $label, string $color): void {
    expect($assessment->getLabel())->toBe($label)
        ->and($assessment->getColor())->toBe($color);
})->with([
    [EvidenceAssessment::Supports, 'Supports', 'success'],
    [EvidenceAssessment::Refutes, 'Refutes', 'danger'],
    [EvidenceAssessment::Irrelevant, 'Irrelevant', 'gray'],
]);
