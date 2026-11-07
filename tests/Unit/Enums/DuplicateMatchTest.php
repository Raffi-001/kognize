<?php

declare(strict_types=1);

use App\Enums\DuplicateMatch;

it('has a label for every match', function (DuplicateMatch $match, string $label): void {
    expect($match->label())->toBe($label);
})->with([
    [DuplicateMatch::Exact, 'Exact match'],
    [DuplicateMatch::NearExact, 'Nearly identical'],
    [DuplicateMatch::SubsetOfExisting, 'Part of an existing submission'],
    [DuplicateMatch::ContainsExisting, 'Contains an existing submission'],
    [DuplicateMatch::Similar, 'Similar content'],
    [DuplicateMatch::SameUrl, 'Same source URL'],
]);
