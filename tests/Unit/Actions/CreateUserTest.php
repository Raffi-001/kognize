<?php

declare(strict_types=1);

use App\Actions\CreateUser;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

it('may create a user', function (): void {
    Event::fake([Registered::class]);

    $action = resolve(CreateUser::class);

    $user = $action->handle([
        'name' => 'Test User',
        'email' => 'example@email.com',
    ], 'password');

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('example@email.com')
        ->and($user->password)->not->toBe('password');

    Event::assertDispatched(Registered::class);
});

it('marks the invitation as used', function (): void {
    Event::fake([Registered::class]);

    $invitation = Invitation::factory()->create(['code' => '12345', 'email' => 'example@email.com']);
    $other = Invitation::factory()->create(['code' => '54321']);

    $user = resolve(CreateUser::class)->handle([
        'name' => 'Test User',
        'email' => 'example@email.com',
    ], 'password', '12345');

    expect($invitation->refresh()->invitee_id)->toBe($user->id)
        ->and($invitation->used_at)->not->toBeNull()
        ->and($other->refresh()->used_at)->toBeNull();
});

it('records the creator of the invitation as the referrer', function (): void {
    Event::fake([Registered::class]);

    $inviter = User::factory()->create();
    Invitation::factory()->for($inviter, 'inviter')->create(['code' => '12345', 'email' => null]);

    $user = resolve(CreateUser::class)->handle([
        'name' => 'Test User',
        'email' => 'anyone@example.com',
    ], 'password', '12345');

    expect($user->referrer_id)->toBe($inviter->id)
        ->and($inviter->referees()->pluck('id')->all())->toBe([$user->id]);
});

it('creates no user when the code was already used', function (): void {
    Event::fake([Registered::class]);

    Invitation::factory()->used()->create(['code' => '12345']);
    $users = User::query()->count();

    expect(fn (): User => resolve(CreateUser::class)->handle([
        'name' => 'Test User',
        'email' => 'example@email.com',
    ], 'password', '12345'))->toThrow(ValidationException::class, 'This invitation code is invalid or has already been used.');

    expect(User::query()->count())->toBe($users);

    Event::assertNotDispatched(Registered::class);
});

it('creates users without a referrer when no code is given', function (): void {
    $user = resolve(CreateUser::class)->handle([
        'name' => 'Test User',
        'email' => 'example@email.com',
    ], 'password');

    expect($user->referrer_id)->toBeNull();
});
