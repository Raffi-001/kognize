<?php

declare(strict_types=1);

use App\Enums\Badge;
use App\Enums\BadgeTier;

it('describes every badge', function (Badge $badge): void {
    expect($badge->label())->toBeString()->not->toBeEmpty()
        ->and($badge->description())->toEndWith('.')
        ->and($badge->tier())->toBeInstanceOf(BadgeTier::class);
})->with(Badge::cases());

it('has gold, silver and bronze badges', function (): void {
    expect(Badge::Pillar->tier())->toBe(BadgeTier::Gold)
        ->and(Badge::ExcellentNote->tier())->toBe(BadgeTier::Gold)
        ->and(Badge::FactChecker->tier())->toBe(BadgeTier::Silver)
        ->and(Badge::Supporter->tier())->toBe(BadgeTier::Bronze);
});

it('awards note badges per note at rising scores', function (): void {
    $perNote = array_values(array_filter(Badge::cases(), fn (Badge $badge): bool => $badge->isPerNote()));

    expect($perNote)->toBe([Badge::GoodNote, Badge::GreatNote, Badge::ExcellentNote])
        ->and(array_map(fn (Badge $badge): int => $badge->noteScore(), $perNote))->toBe([5, 15, 40])
        ->and(Badge::GreatNote->description())->toBe('A community note reached a score of 15.')
        ->and(Badge::Supporter->noteScore())->toBe(0);
});
