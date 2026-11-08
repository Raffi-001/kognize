<?php

declare(strict_types=1);

use App\Models\AnalysisNote;
use App\Models\Newsletter;
use App\Models\Poll;
use App\Models\Submission;
use App\Models\User;

test('to array', function (): void {
    $newsletter = Newsletter::factory()->create()->refresh();

    expect(array_keys($newsletter->toArray()))
        ->toBe([
            'id',
            'subject',
            'intro',
            'created_at',
            'updated_at',
        ]);
});

it('has ordered submissions, polls, questions and recipients', function (): void {
    $newsletter = Newsletter::factory()->create();
    $submissions = Submission::factory()->count(2)->create();
    $poll = Poll::factory()->published()->create();
    $question = AnalysisNote::factory()->question()->create();
    $user = User::factory()->create();

    $newsletter->submissions()->attach($submissions->reverse()->pluck('id'));
    $newsletter->polls()->attach($poll);
    $newsletter->questions()->attach($question);
    $newsletter->recipients()->attach($user);

    expect($newsletter->submissions->pluck('id')->all())->toBe($submissions->pluck('id')->all())
        ->and($newsletter->polls->sole()->is($poll))->toBeTrue()
        ->and($newsletter->questions->sole()->is($question))->toBeTrue()
        ->and($newsletter->recipients->sole()->is($user))->toBeTrue()
        ->and($newsletter->recipients->sole()->pivot->created_at)->not->toBeNull();
});
