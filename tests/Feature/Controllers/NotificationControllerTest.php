<?php

declare(strict_types=1);

use App\Enums\NotificationKind;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function activity(string $url = 'https://kognize.test/submissions/1'): ActivityNotification
{
    return new ActivityNotification(
        NotificationKind::CommentPosted,
        ['id' => 'someone', 'name' => 'Ani'],
        ['type' => 'user_note', 'id' => 1, 'title' => 'Coffee ad'],
        $url,
        'Nice catch',
    );
}

it('requires authentication', function (): void {
    $this->get(route('notifications.index'))->assertRedirectToRoute('login');
});

it('lists the notifications of the user, newest first', function (): void {
    $user = User::factory()->create();
    $user->notify(activity());
    $this->travel(1)->minute();
    $user->notify(activity());
    $user->unreadNotifications()->latest()->first()?->markAsRead();
    User::factory()->create()->notify(activity());

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('notification/Index')
            ->has('notifications.data', 2)
            ->where('notifications.data.0.headline', 'Ani commented on “Coffee ad”')
            ->where('notifications.data.0.kind', 'comment_posted')
            ->where('notifications.data.0.excerpt', 'Nice catch')
            ->where('notifications.data.0.actors', ['Ani'])
            ->where('notifications.data.0.is_read', true)
            ->where('notifications.data.1.is_read', false)
            ->where('notifications.meta.total', 2));
});

it('marks a notification as read and opens what it is about', function (): void {
    $user = User::factory()->create();
    $user->notify(activity(route('questions.index')));

    $notification = $user->notifications()->sole();

    $this->actingAs($user)
        ->patch(route('notifications.update', $notification->id))
        ->assertRedirect(route('questions.index'));

    expect($notification->refresh()->read_at)->not->toBeNull();
});

it('falls back to the notification list when the notification has no link', function (): void {
    $user = User::factory()->create();
    $notification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'comment_posted',
        'data' => [],
    ]);

    $this->actingAs($user)
        ->patch(route('notifications.update', $notification->id))
        ->assertRedirectToRoute('notifications.index');
});

it('does not open notifications of other users', function (): void {
    $other = User::factory()->create();
    $other->notify(activity());

    $this->actingAs(User::factory()->create())
        ->patch(route('notifications.update', $other->notifications()->sole()->id))
        ->assertNotFound();

    expect($other->unreadNotifications()->count())->toBe(1);
});
