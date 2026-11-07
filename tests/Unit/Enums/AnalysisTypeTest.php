<?php

declare(strict_types=1);

use App\Enums\AnalysisType;

it('has a label for every type', function (AnalysisType $type, string $label): void {
    expect($type->label())->toBe($label);
})->with([
    [AnalysisType::Fallacy, 'Reasoning gaps'],
    [AnalysisType::Rhetoric, 'Rhetoric'],
    [AnalysisType::Questions, 'Open questions'],
]);

it('has a catalog with Armenian names for fallacies and rhetoric only', function (): void {
    expect(AnalysisType::Fallacy->catalog())->toHaveKey('False dilemma')
        ->and(AnalysisType::Fallacy->catalog()['False dilemma']['armenian'])->toBe('Կեղծ երկընտրանք')
        ->and(AnalysisType::Rhetoric->catalog())->toHaveKey('Hyperbole')
        ->and(AnalysisType::Questions->catalog())->toBe([]);
});

it('limits the number of findings per type', function (): void {
    config(['analysis.max_findings.questions' => 2]);

    expect(AnalysisType::Questions->maxFindings())->toBe(2)
        ->and(AnalysisType::Fallacy->maxFindings())->toBe(6);
});
