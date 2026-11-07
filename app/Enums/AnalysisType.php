<?php

declare(strict_types=1);

namespace App\Enums;

enum AnalysisType: string
{
    case Fallacy = 'fallacy';
    case Rhetoric = 'rhetoric';
    case Questions = 'questions';

    public function label(): string
    {
        return match ($this) {
            self::Fallacy => 'Reasoning gaps',
            self::Rhetoric => 'Rhetoric',
            self::Questions => 'Open questions',
        };
    }

    /**
     * The closed list of names the model may use, keyed by English name.
     *
     * @return array<string, array{armenian: string, definition: string}>
     */
    public function catalog(): array
    {
        /** @var array<string, array{armenian: string, definition: string}> $catalog */
        $catalog = config()->array('analysis.catalog.'.$this->value, []);

        return $catalog;
    }

    public function maxFindings(): int
    {
        return config()->integer('analysis.max_findings.'.$this->value);
    }
}
