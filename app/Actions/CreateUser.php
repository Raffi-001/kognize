<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;

final readonly class CreateUser
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes, #[SensitiveParameter] string $password, ?string $invitationCode = null): User
    {
        return DB::transaction(function () use ($attributes, $password, $invitationCode): User {
            $user = User::query()->create([
                ...$attributes,
                'password' => $password,
            ]);

            if ($invitationCode !== null) {
                Invitation::query()
                    ->where('code', $invitationCode)
                    ->whereNull('used_at')
                    ->update(['invitee_id' => $user->id, 'used_at' => now()]);
            }

            event(new Registered($user));

            return $user;
        });
    }
}
