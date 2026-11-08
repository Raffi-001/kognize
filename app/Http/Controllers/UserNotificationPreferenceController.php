<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationKind;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserNotificationPreferenceController
{
    public function edit(#[CurrentUser] User $user): Response
    {
        return Inertia::render('user-notification-preference/Edit', [
            'kinds' => array_map(fn (NotificationKind $kind): array => [
                'value' => $kind->value,
                'label' => $kind->label(),
                'description' => $kind->description(),
            ], NotificationKind::cases()),
            'muted' => $user->muted_notifications ?? [],
            'digest' => $user->notification_digest,
        ]);
    }

    public function update(UpdateNotificationPreferencesRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $user->update([
            'muted_notifications' => array_values(array_filter($request->array('muted'), is_string(...))),
            'notification_digest' => $request->boolean('digest'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notification settings saved.')]);

        return to_route('notification-preferences.edit');
    }
}
