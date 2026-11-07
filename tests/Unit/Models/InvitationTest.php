<?php

declare(strict_types=1);

use App\Models\Invitation;
use App\Models\User;
use Carbon\CarbonInterface;

test('to array', function (): void {
    $invitation = Invitation::factory()->create()->refresh();

    expect(array_keys($invitation->toArray()))
        ->toBe([
            'id',
            'code',
            'email',
            'inviter_id',
            'invitee_id',
            'used_at',
            'created_at',
            'updated_at',
        ]);
});

it('belongs to an inviter and an invitee', function (): void {
    $inviter = User::factory()->create();
    $invitee = User::factory()->create();
    $invitation = Invitation::factory()->for($inviter, 'inviter')->create([
        'invitee_id' => $invitee->id,
        'used_at' => now(),
    ]);

    expect($invitation->inviter->is($inviter))->toBeTrue()
        ->and($invitation->invitee?->is($invitee))->toBeTrue()
        ->and($invitation->used_at)->toBeInstanceOf(CarbonInterface::class);
});

it('has no invitee until used', function (): void {
    $invitation = Invitation::factory()->create();

    expect($invitation->invitee)->toBeNull()
        ->and($invitation->used_at)->toBeNull();
});
