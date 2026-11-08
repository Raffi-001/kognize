<?php

declare(strict_types=1);

use App\Enums\NotificationKind;
use App\Models\User;
use App\Notifications\ActivityNotification;

it('marks every notification of the user as read', function (): void {
    $notification = new ActivityNotification(
        NotificationKind::AnalysisCompleted,
        null,
        ['type' => 'submission', 'id' => 1, 'title' => 'Coffee ad'],
        'https://kognize.test',
    );
    $user = User::factory()->create();
    $user->notify($notification);
    $user->notify($notification);

    $other = User::factory()->create();
    $other->notify($notification);

    $this->actingAs($user)
        ->fromRoute('notifications.index')
        ->post(route('notifications.read.store'))
        ->assertRedirectToRoute('notifications.index');

    expect($user->unreadNotifications()->count())->toBe(0)
        ->and($other->unreadNotifications()->count())->toBe(1);
});

it('requires authentication', function (): void {
    $this->post(route('notifications.read.store'))->assertRedirectToRoute('login');
});
