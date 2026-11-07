<?php

declare(strict_types=1);

namespace App\Enums;

enum Ethics: string
{
    case Neutral = 'neutral';
    case Questionable = 'questionable';
    case Unethical = 'unethical';
}
