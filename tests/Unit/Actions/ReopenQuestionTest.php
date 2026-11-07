<?php

declare(strict_types=1);

use App\Actions\ReopenQuestion;
use App\Enums\QuestionStatus;
use App\Enums\Verdict;
use App\Models\AnalysisNote;
use App\Models\User;

it('reopens a resolved question', function (): void {
    $question = AnalysisNote::factory()->question()->create([
        'status' => QuestionStatus::Resolved,
        'verdict' => Verdict::Supported,
        'resolution' => 'Հաստատված է։',
        'resolved_by' => User::factory()->admin(),
        'resolved_at' => now(),
    ]);

    $result = resolve(ReopenQuestion::class)->handle($question);

    expect($result->refresh()->status)->toBe(QuestionStatus::Open)
        ->and($result->verdict)->toBeNull()
        ->and($result->resolution)->toBeNull()
        ->and($result->resolved_by)->toBeNull()
        ->and($result->resolved_at)->toBeNull();
});
