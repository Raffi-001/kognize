<?php

declare(strict_types=1);

namespace App\Enums;

enum BadgeTier: string
{
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';
}
