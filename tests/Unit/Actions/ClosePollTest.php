<?php

declare(strict_types=1);

use App\Actions\ClosePoll;
use App\Enums\PollStatus;
use App\Models\Poll;

it('closes a poll', function (): void {
    $poll = Poll::factory()->published()->create();

    $result = resolve(ClosePoll::class)->handle($poll);

    expect($result->refresh()->status)->toBe(PollStatus::Closed)
        ->and($result->closed_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});
