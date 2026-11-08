<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class NotificationController
{
    public function index(#[CurrentUser] User $user): Response
    {
        return Inertia::render('notification/Index', [
            'notifications' => NotificationResource::collection($user->notifications()->paginate(20)),
        ]);
    }

    /**
     * Mark the notification as read and open what it is about.
     */
    public function update(string $notification, #[CurrentUser] User $user): RedirectResponse
    {
        $record = $user->notifications()->findOrFail($notification);

        $record->markAsRead();

        $url = $record->data['url'] ?? null;

        return redirect()->to(is_string($url) ? $url : route('notifications.index'));
    }
}
