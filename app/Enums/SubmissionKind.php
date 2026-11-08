<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SubmissionKind: string implements HasLabel
{
    case Argument = 'argument';
    case Informational = 'informational';
    case Personal = 'personal';

    public function label(): string
    {
        return match ($this) {
            self::Argument => 'Argument',
            self::Informational => 'Informational text',
            self::Personal => 'Personal impression',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
