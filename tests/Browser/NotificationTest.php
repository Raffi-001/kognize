<?php

declare(strict_types=1);

use App\Actions\CreateComment;
use App\Models\User;
use App\Models\UserNote;

it('shows new activity under the bell and opens it', function (): void {
    $note = UserNote::factory()->create();
    $owner = $note->user;
    $owner->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
    resolve(CreateComment::class)->handle($note, User::factory()->create(['name' => 'Ani']), 'Nice catch');

    $this->actingAs($owner);

    $page = visit(route('submissions.index'));

    $page->assertSeeIn('@notification-count', '1')
        ->click('@notification-bell')
        ->assertSee('Ani commented on')
        ->assertSee('Nice catch')
        ->click('@notification')
        ->assertPathIs('/submissions/'.$note->submission->id)
        ->assertMissing('@notification-count')
        ->assertNoJavaScriptErrors();

    expect($owner->unreadNotifications()->count())->toBe(0);
});

it('marks everything as read from the bell', function (): void {
    $note = UserNote::factory()->create();
    $owner = $note->user;
    foreach (['Ani', 'Aram'] as $name) {
        resolve(CreateComment::class)->handle($note, User::factory()->create(['name' => $name]), 'Hi');
    }
    resolve(CreateComment::class)->handle(UserNote::factory()->for($owner)->create(), User::factory()->create(), 'Hey');

    $this->actingAs($owner);

    visit(route('submissions.index'))
        ->assertSeeIn('@notification-count', '2')
        ->click('@notification-bell')
        ->assertSee('Aram and Ani commented on')
        ->click('@notifications-mark-all-read')
        ->assertMissing('@notification-count')
        ->assertNoJavaScriptErrors();

    expect($owner->unreadNotifications()->count())->toBe(0);
});

it('lets people mute kinds of notifications', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    visit(route('notification-preferences.edit'))
        ->assertSee('In-app notifications')
        ->click('@notification-kind-comment_posted')
        ->click('@notification-digest')
        ->click('@save-notification-preferences')
        ->assertSee('Notification settings saved.')
        ->assertNoJavaScriptErrors();

    expect($user->refresh()->muted_notifications)->toBe(['comment_posted'])
        ->and($user->notification_digest)->toBeTrue();
});
