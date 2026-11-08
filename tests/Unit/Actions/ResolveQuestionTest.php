<?php

declare(strict_types=1);

use App\Actions\ResolveQuestion;
use App\Enums\QuestionStatus;
use App\Enums\Verdict;
use App\Models\AnalysisNote;
use App\Models\Evidence;
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

it('tells everyone who cited a source that the question was resolved', function (): void {
    $question = AnalysisNote::factory()->question()->create(['aspect_label' => 'Արդյո՞ք ծախսերն աճել են։']);
    $evidence = Evidence::factory()->for($question, 'question')->count(2)->create();
    Evidence::factory()->for($question, 'question')->for($evidence[0]->user)->create();

    resolve(ResolveQuestion::class)->handle($question, User::factory()->admin()->create(), Verdict::Supported, 'Հաստատված է։');

    foreach ($evidence as $item) {
        $notification = $item->user->notifications()->sole();

        expect($notification->type)->toBe('question_resolved')
            ->and($notification->data['subject']['title'])->toBe('Արդյո՞ք ծախսերն աճել են։')
            ->and($notification->data['url'])->toBe(route('questions.show', $question))
            ->and($notification->data['excerpt'])->toBe('Supported by evidence');
    }
});
