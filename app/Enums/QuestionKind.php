<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum QuestionKind: string implements HasColor, HasLabel
{
    case Factual = 'factual';
    case Value = 'value';

    public function getLabel(): string
    {
        return match ($this) {
            self::Factual => 'Factual',
            self::Value => 'Value',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Factual => 'info',
            self::Value => 'primary',
        };
    }
}
