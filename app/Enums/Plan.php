<?php

declare(strict_types=1);

namespace App\Enums;

enum Plan: string
{
    case Standard = 'standard';
    case Pro = 'pro';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::Pro => 'Pro',
        };
    }

    public function dailySubmissions(): int
    {
        return config()->integer(sprintf('kognize.plans.%s.daily_submissions', $this->value));
    }

    public function invitations(): int
    {
        return config()->integer(sprintf('kognize.plans.%s.invitations', $this->value));
    }
}
