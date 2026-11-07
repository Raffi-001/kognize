<?php

declare(strict_types=1);

use App\Actions\PublishPoll;
use App\Enums\PollStatus;
use App\Models\Poll;
use App\Models\User;

it('publishes a poll', function (Closure $poll): void {
    $poll = $poll();
    $admin = User::factory()->admin()->create();

    $result = resolve(PublishPoll::class)->handle($poll, $admin);

    expect($result->refresh()->status)->toBe(PollStatus::Published)
        ->and($result->approved_by)->toBe($admin->id)
        ->and($result->published_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($result->closed_at)->toBeNull();
})->with([
    'draft' => [fn () => Poll::factory()->create()],
    'closed' => [fn () => Poll::factory()->closed()->create(['published_at' => now()->subWeek()])],
]);
