<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Verdict: string implements HasColor, HasLabel
{
    case Supported = 'supported';
    case Refuted = 'refuted';
    case Contested = 'contested';

    public function label(): string
    {
        return match ($this) {
            self::Supported => 'Supported by evidence',
            self::Refuted => 'Refuted by evidence',
            self::Contested => 'Contested',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Supported => 'success',
            self::Refuted => 'danger',
            self::Contested => 'warning',
        };
    }
}
