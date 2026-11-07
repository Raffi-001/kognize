<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Stance: string implements HasColor, HasLabel
{
    case Supports = 'supports';
    case Refutes = 'refutes';

    public function getLabel(): string
    {
        return match ($this) {
            self::Supports => 'Supports the claim',
            self::Refutes => 'Refutes the claim',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Supports => 'success',
            self::Refutes => 'danger',
        };
    }
}
