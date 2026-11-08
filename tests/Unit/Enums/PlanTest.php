<?php

declare(strict_types=1);

use App\Enums\Plan;

it('has a label for every plan', function (Plan $plan, string $label): void {
    expect($plan->label())->toBe($label);
})->with([
    [Plan::Standard, 'Standard'],
    [Plan::Pro, 'Pro'],
]);

it('reads its limits from config', function (Plan $plan): void {
    config([
        'kognize.plans.'.$plan->value.'.daily_submissions' => 7,
        'kognize.plans.'.$plan->value.'.invitations' => 4,
    ]);

    expect($plan->dailySubmissions())->toBe(7)
        ->and($plan->invitations())->toBe(4);
})->with(Plan::cases());

it('gives pro more than standard', function (): void {
    expect(Plan::Pro->dailySubmissions())->toBeGreaterThan(Plan::Standard->dailySubmissions())
        ->and(Plan::Pro->invitations())->toBeGreaterThan(Plan::Standard->invitations());
});
