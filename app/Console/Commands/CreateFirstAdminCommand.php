<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Bootstraps an invite-only installation: creates the first, verified
 * administrator, who can then generate invitation codes in the admin panel.
 */
#[Description('Create the first administrator. Only works while there are no users.')]
#[Signature('kognize:create-first-admin {email : The email address of the administrator}')]
final class CreateFirstAdminCommand extends Command
{
    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->components->error('Users already exist. Manage them in the admin panel instead.');

            return self::FAILURE;
        }

        $argument = $this->argument('email');
        $email = is_string($argument) ? mb_strtolower($argument) : '';

        if (Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']])->fails()) {
            $this->components->error('That is not a valid email address.');

            return self::FAILURE;
        }

        $name = text(label: 'Name', required: true);
        $password = password(
            label: 'Password',
            required: true,
            validate: fn (string $value): ?string => Validator::make(
                ['password' => $value],
                ['password' => [Password::defaults()]],
            )->errors()->first('password') ?: null,
        );

        $user = User::query()->forceCreate([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->components->info(sprintf('Administrator %s created. Sign in and open /admin to generate invitation codes.', $user->email));

        return self::SUCCESS;
    }
}
