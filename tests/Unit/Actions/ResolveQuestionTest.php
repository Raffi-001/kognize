<?php

declare(strict_types=1);

use App\Actions\ResolveQuestion;
use App\Enums\QuestionStatus;
use App\Enums\Verdict;
use App\Models\AnalysisNote;
use App\Models\User;

it('resolves a question with a verdict', function (): void {
    $question = AnalysisNote::factory()->question()->create();
    $moderator = User::factory()->admin()->create();

    $result = resolve(ResolveQuestion::class)->handle($question, $moderator, Verdict::Refuted, '  Աղբյուրը հերքում է։  ');

    expect($result->refresh()->status)->toBe(QuestionStatus::Resolved)
        ->and($result->verdict)->toBe(Verdict::Refuted)
        ->and($result->resolution)->toBe('Աղբյուրը հերքում է։')
        ->and($result->resolver->is($moderator))->toBeTrue()
        ->and($result->resolved_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});
