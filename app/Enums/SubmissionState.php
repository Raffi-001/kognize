<?php

declare(strict_types=1);

namespace App\Enums;

enum SubmissionState: string
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

    public function isInProgress(): bool
    {
        return in_array($this, [self::Pending, self::Processing], true);
    }
}
