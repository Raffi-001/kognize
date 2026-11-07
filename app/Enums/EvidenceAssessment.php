<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EvidenceAssessment: string implements HasColor, HasLabel
{
    case Supports = 'supports';
    case Refutes = 'refutes';
    case Irrelevant = 'irrelevant';

    public function getLabel(): string
    {
        return match ($this) {
            self::Supports => 'Supports',
            self::Refutes => 'Refutes',
            self::Irrelevant => 'Irrelevant',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Supports => 'success',
            self::Refutes => 'danger',
            self::Irrelevant => 'gray',
        };
    }
}
