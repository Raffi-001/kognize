<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EvidenceVerification: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Verified = 'verified';
    case NotFound = 'not_found';
    case Unreachable = 'unreachable';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Checking',
            self::Verified => 'Quote found',
            self::NotFound => 'Quote not found',
            self::Unreachable => 'Page unreachable',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Verified => 'success',
            self::NotFound => 'danger',
            self::Unreachable => 'danger',
        };
    }
}
