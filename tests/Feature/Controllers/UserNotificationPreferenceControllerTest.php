<?php

declare(strict_types=1);

use App\Enums\NotificationKind;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the notification settings', function (): void {
    $user = User::factory()->create(['muted_notifications' => ['comment_posted'], 'notification_digest' => true]);

    $this->actingAs($user)
        ->get(route('notification-preferences.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('user-notification-preference/Edit')
            ->has('kinds', count(NotificationKind::cases()))
            ->where('kinds.0', [
                'value' => 'analysis_completed',
                'label' => 'Analysis ready',
                'description' => 'When an analysis of your text finishes.',
            ])
            ->where('muted', ['comment_posted'])
            ->where('digest', true));
});

it('shows defaults when nothing was chosen yet', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('notification-preferences.edit'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('muted', [])
            ->where('digest', false));
});

it('saves the notification settings', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('notification-preferences.update'), [
            'muted' => ['comment_posted', 'invitee_joined'],
            'digest' => true,
        ])
        ->assertRedirectToRoute('notification-preferences.edit');

    expect($user->refresh()->muted_notifications)->toBe(['comment_posted', 'invitee_joined'])
        ->and($user->notification_digest)->toBeTrue();
});

it('validates the notification settings', function (array $data, string $error): void {
    $this->actingAs(User::factory()->create())
        ->fromRoute('notification-preferences.edit')
        ->put(route('notification-preferences.update'), $data)
        ->assertRedirectToRoute('notification-preferences.edit')
        ->assertSessionHasErrors($error);
})->with([
    'missing list' => [['digest' => true], 'muted'],
    'unknown kind' => [['muted' => ['spam'], 'digest' => true], 'muted.0'],
    'missing digest' => [['muted' => []], 'digest'],
]);

it('requires authentication', function (): void {
    $this->get(route('notification-preferences.edit'))->assertRedirectToRoute('login');
});
