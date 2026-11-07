<?php

declare(strict_types=1);

use App\Models\Poll;
use App\Models\User;

it('lets only admins see and create polls', function (string $ability, bool $isAdmin): void {
    $user = User::factory()->create(['is_admin' => $isAdmin]);

    expect($user->can($ability, Poll::class))->toBe($isAdmin);
})->with(['viewAny', 'view', 'create'])->with([
    'admin' => [true],
    'user' => [false],
]);

it('lets admins change only drafts', function (string $ability, Closure $poll, bool $isAdmin, bool $allowed): void {
    $user = User::factory()->create(['is_admin' => $isAdmin]);

    expect($user->can($ability, $poll()))->toBe($allowed);
})->with(['update', 'delete'])->with([
    'admin and draft' => [fn () => Poll::factory()->create(), true, true],
    'admin and published' => [fn () => Poll::factory()->published()->create(), true, false],
    'admin and closed' => [fn () => Poll::factory()->closed()->create(), true, false],
    'user and draft' => [fn () => Poll::factory()->create(), false, false],
]);
