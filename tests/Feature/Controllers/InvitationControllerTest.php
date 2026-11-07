<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    $this->withoutVite();
});

it('requires authentication', function (): void {
    $this->get(route('invitations.index'))
        ->assertRedirectToRoute('login');
});

it('lists the invitations of the user', function (): void {
    $user = User::factory()->create();
    $invitee = User::factory()->create(['name' => 'Invitee']);

    $used = Invitation::factory()->for($user, 'inviter')->create([
        'email' => 'used@example.com',
        'code' => '11111',
        'invitee_id' => $invitee->id,
        'used_at' => now(),
    ]);
    $pending = Invitation::factory()->for($user, 'inviter')->create(['email' => 'pending@example.com', 'code' => '22222']);
    Invitation::factory()->create();

    $response = $this->actingAs($user)->get(route('invitations.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('invitation/Index', false)
            ->has('invitations', 2)
            ->where('invitations.0', [
                'id' => $pending->id,
                'email' => 'pending@example.com',
                'code' => '22222',
                'is_used' => false,
                'invitee' => null,
                'used_at_human' => null,
                'created_at_human' => '0 seconds ago',
            ])
            ->where('invitations.1', [
                'id' => $used->id,
                'email' => 'used@example.com',
                'code' => '11111',
                'is_used' => true,
                'invitee' => ['id' => $invitee->id, 'name' => 'Invitee'],
                'used_at_human' => '0 seconds ago',
                'created_at_human' => '0 seconds ago',
            ]));
});

it('may send an invitation', function (): void {
    Mail::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('invitations.index')
        ->post(route('invitations.store'), ['email' => 'friend@example.com']);

    $response->assertRedirectToRoute('invitations.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

    $invitation = Invitation::query()->sole();

    expect($invitation->inviter->is($user))->toBeTrue()
        ->and($invitation->email)->toBe('friend@example.com');

    Mail::assertQueued(InvitationMail::class);
});

it('may invite an email whose previous invitation was used', function (): void {
    Mail::fake();

    Invitation::factory()->used()->create(['email' => 'friend@example.com']);

    $this->actingAs(User::factory()->create())
        ->fromRoute('invitations.index')
        ->post(route('invitations.store'), ['email' => 'friend@example.com'])
        ->assertSessionHasNoErrors();

    Mail::assertQueued(InvitationMail::class);
});

it('validates the email', function (Closure $setup, array $data, string $message): void {
    Mail::fake();

    $setup();

    $this->actingAs(User::factory()->create())
        ->fromRoute('invitations.index')
        ->post(route('invitations.store'), $data)
        ->assertRedirectToRoute('invitations.index')
        ->assertSessionHasErrors(['email' => $message]);

    Mail::assertNothingQueued();
})->with([
    'missing' => [fn (): null => null, [], 'The email field is required.'],
    'invalid' => [fn (): null => null, ['email' => 'not-an-email'], 'The email field must be a valid email address.'],
    'existing user' => [fn () => User::factory()->create(['email' => 'friend@example.com']), ['email' => 'friend@example.com'], 'This person already has an account or a pending invitation.'],
    'pending invitation' => [fn () => Invitation::factory()->create(['email' => 'friend@example.com']), ['email' => 'friend@example.com'], 'This person already has an account or a pending invitation.'],
]);

it('limits the invitations', function (): void {
    Mail::fake();

    $user = User::factory()->create(['plan' => Plan::Standard]);

    Invitation::factory()->for($user, 'inviter')->count(Plan::Standard->invitations())->create();

    $this->actingAs($user)
        ->fromRoute('invitations.index')
        ->post(route('invitations.store'), ['email' => 'friend@example.com'])
        ->assertRedirectToRoute('invitations.index')
        ->assertSessionHasErrors(['limit' => 'You have no invitations left.']);

    Mail::assertNothingQueued();
});
