<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates the first administrator', function (): void {
    $this->artisan('kognize:create-first-admin', ['email' => 'Founder@Example.com'])
        ->expectsQuestion('Name', 'Founder')
        ->expectsQuestion('Password', 'a-long-secret-password')
        ->expectsOutputToContain('Administrator founder@example.com created.')
        ->assertSuccessful();

    $user = User::query()->sole();

    expect($user->email)->toBe('founder@example.com')
        ->and($user->is_admin)->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->referrer_id)->toBeNull()
        ->and(Hash::check('a-long-secret-password', $user->password))->toBeTrue();
});

it('refuses to run once users exist', function (): void {
    User::factory()->create();

    $this->artisan('kognize:create-first-admin', ['email' => 'founder@example.com'])
        ->expectsOutputToContain('Users already exist.')
        ->assertFailed();

    expect(User::query()->count())->toBe(1);
});

it('rejects an invalid email address', function (): void {
    $this->artisan('kognize:create-first-admin', ['email' => 'not-an-email'])
        ->expectsOutputToContain('That is not a valid email address.')
        ->assertFailed();

    expect(User::query()->exists())->toBeFalse();
});
