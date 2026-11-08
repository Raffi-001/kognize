<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationKind;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

final readonly class CreateUser
{
    public function __construct(private NotifyActivity $notify) {}

    /**
     * Create the account and, when a code is given, redeem it: each code works
     * once, and the user who created it becomes the new user's referrer.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes, #[SensitiveParameter] string $password, ?string $invitationCode = null): User
    {
        return DB::transaction(function () use ($attributes, $password, $invitationCode): User {
            $invitation = $invitationCode === null ? null : Invitation::query()
                ->where('code', $invitationCode)
                ->whereNull('used_at')
                ->first();

            $user = User::query()->create([
                ...$attributes,
                'password' => $password,
                'referrer_id' => $invitation?->inviter_id,
            ]);

            if ($invitationCode !== null) {
                // Guarding on used_at makes a second, concurrent redemption of the same code fail.
                $redeemed = $invitation === null ? 0 : Invitation::query()
                    ->whereKey($invitation->id)
                    ->whereNull('used_at')
                    ->update(['invitee_id' => $user->id, 'used_at' => now()]);

                throw_if($redeemed === 0, ValidationException::withMessages([
                    'invitation_code' => 'This invitation code is invalid or has already been used.',
                ]));
            }

            $this->notify->handle(
                [$user->referrer],
                NotificationKind::InviteeJoined,
                $user,
                ['type' => 'user', 'id' => $user->id, 'title' => $user->name],
                route('invitations.index'),
            );

            event(new Registered($user));

            return $user;
        });
    }
}
