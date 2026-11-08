<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SubmissionState: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Analyzed = 'analyzed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Queued',
            self::Processing => 'Analyzing',
            self::Analyzed => 'Analyzed',
            self::Failed => 'Failed',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Processing => 'info',
            self::Analyzed => 'success',
            self::Failed => 'danger',
        };
    }

    public function isInProgress(): bool
    {
        return in_array($this, [self::Pending, self::Processing], true);
    }
}
