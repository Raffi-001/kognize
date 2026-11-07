<?php

declare(strict_types=1);

namespace App\Enums;

enum DuplicateMatch: string
{
    case Exact = 'exact';
    case NearExact = 'near_exact';
    case SubsetOfExisting = 'subset_of_existing';
    case ContainsExisting = 'contains_existing';
    case Similar = 'similar';
    case SameUrl = 'same_url';

    public function label(): string
    {
        return match ($this) {
            self::Exact => 'Exact match',
            self::NearExact => 'Nearly identical',
            self::SubsetOfExisting => 'Part of an existing submission',
            self::ContainsExisting => 'Contains an existing submission',
            self::Similar => 'Similar content',
            self::SameUrl => 'Same source URL',
        };
    }
}
