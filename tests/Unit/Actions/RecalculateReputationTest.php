<?php

declare(strict_types=1);

use App\Actions\RecalculateReputation;
use App\Models\ReputationEvent;
use App\Models\User;

it('sums the ledger and tells the user about privileges they unlocked', function (): void {
    $user = User::factory()->create();
    ReputationEvent::factory()->for($user)->count(6)->create(['amount' => 40]);

    resolve(RecalculateReputation::class)->handle($user);

    expect($user->refresh()->reputation)->toBe(241)
        ->and($user->notifications()->where('type', 'privilege_unlocked')->get()->pluck('data.subject.title')->sort()->values()->all())
        ->toBe(['Extra daily analysis', 'Vote down']);
});

it('does nothing when nothing changed', function (): void {
    $user = User::factory()->create();

    resolve(RecalculateReputation::class)->handle($user);

    expect($user->refresh()->reputation)->toBe(1)
        ->and($user->notifications()->count())->toBe(0);
});

it('does not announce privileges again after losing and regaining them', function (): void {
    $user = User::factory()->create(['reputation' => 60]);
    $event = ReputationEvent::factory()->for($user)->create(['amount' => 59]);

    resolve(RecalculateReputation::class)->handle($user);
    expect($user->notifications()->count())->toBe(0);

    $event->delete();
    resolve(RecalculateReputation::class)->handle($user->refresh());

    expect($user->refresh()->reputation)->toBe(1)
        ->and($user->notifications()->count())->toBe(0);
});
