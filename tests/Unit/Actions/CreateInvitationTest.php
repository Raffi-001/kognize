<?php

declare(strict_types=1);

use App\Actions\CreateInvitation;
use App\Mail\InvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('creates an invitation and mails it', function (): void {
    Mail::fake();

    $inviter = User::factory()->create();

    $invitation = resolve(CreateInvitation::class)->handle($inviter, 'Friend@Example.com');

    expect($invitation->inviter->is($inviter))->toBeTrue()
        ->and($invitation->email)->toBe('friend@example.com')
        ->and($invitation->code)->toMatch('/^\d{5}$/')
        ->and($invitation->used_at)->toBeNull();

    Mail::assertQueued(InvitationMail::class, fn (InvitationMail $mail): bool => $mail->hasTo('friend@example.com')
        && $mail->invitation->is($invitation)
        && $mail->inviter->is($inviter));
});

it('generates unique codes', function (): void {
    Mail::fake();

    $inviter = User::factory()->create();

    $codes = collect(range(1, 20))
        ->map(fn (int $index): string => resolve(CreateInvitation::class)->handle($inviter, sprintf('friend%d@example.com', $index))->code);

    expect($codes->unique())->toHaveCount(20);
});
