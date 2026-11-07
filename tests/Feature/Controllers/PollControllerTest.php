<?php

declare(strict_types=1);

use App\Enums\QuestionKind;
use App\Models\AnalysisNote;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use App\Models\User;

beforeEach(function (): void {
    $this->withoutVite();
});

it('requires authentication', function (): void {
    $this->get(route('polls.index'))
        ->assertRedirectToRoute('login');
});

it('lists the public polls with open ones first', function (): void {
    $closed = Poll::factory()->closed()->withOptions()->create(['published_at' => now()->subDay()]);
    $older = Poll::factory()->published()->withOptions()->create(['published_at' => now()->subWeek()]);
    $newer = Poll::factory()->published()->withOptions()->create();
    Poll::factory()->withOptions()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('polls.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('poll/Index', false)
            ->has('polls', 3)
            ->where('polls.0.id', $newer->id)
            ->where('polls.1.id', $older->id)
            ->where('polls.1.published_at_human', '1 week ago')
            ->where('polls.2.id', $closed->id));
});

it('hides the results and the source until the user answers', function (): void {
    $user = User::factory()->create();
    $submission = Submission::factory()->create(['title' => 'Հոդված']);
    $analysis = SubmissionAnalysis::factory()->for($submission)->create();
    $question = AnalysisNote::factory()->question(QuestionKind::Value)->create(['submission_analysis_id' => $analysis->id]);
    $poll = Poll::factory()->published()->withOptions(['Այո', 'Ոչ'])->create(['analysis_note_id' => $question->id, 'question' => 'Հարց']);

    $this->actingAs($user)
        ->get(route('polls.index'))
        ->assertInertia(fn ($page) => $page
            ->where('polls.0.id', $poll->id)
            ->where('polls.0.question', 'Հարց')
            ->where('polls.0.status', 'published')
            ->where('polls.0.is_open', true)
            ->where('polls.0.options', [
                ['id' => $poll->options[0]->id, 'label' => 'Այո'],
                ['id' => $poll->options[1]->id, 'label' => 'Ոչ'],
            ])
            ->where('polls.0.my_option_id', null)
            ->where('polls.0.my_reason', null)
            ->where('polls.0.results', null)
            ->where('polls.0.source', null));

    PollVote::factory()->for($user)->create(['poll_option_id' => $poll->options[1]->id, 'reason' => 'Որովհետև']);
    PollVote::factory()->count(3)->create(['poll_option_id' => $poll->options[0]->id]);

    $this->actingAs($user)
        ->get(route('polls.index'))
        ->assertInertia(fn ($page) => $page
            ->where('polls.0.my_option_id', $poll->options[1]->id)
            ->where('polls.0.my_reason', 'Որովհետև')
            ->where('polls.0.results', [
                'id' => $poll->id,
                'question' => 'Հարց',
                'status' => 'published',
                'total_votes' => 4,
                'options' => [
                    ['id' => $poll->options[0]->id, 'label' => 'Այո', 'votes' => 3, 'share' => 75],
                    ['id' => $poll->options[1]->id, 'label' => 'Ոչ', 'votes' => 1, 'share' => 25],
                ],
            ])
            ->where('polls.0.source', ['submission_id' => $submission->id, 'title' => 'Հոդված']));
});

it('reveals the results of closed polls', function (): void {
    Poll::factory()->closed()->withOptions(['Այո', 'Ոչ'])->create();

    $this->actingAs(User::factory()->create())
        ->get(route('polls.index'))
        ->assertInertia(fn ($page) => $page
            ->where('polls.0.is_open', false)
            ->where('polls.0.results.total_votes', 0)
            ->where('polls.0.results.options.0.share', 0)
            ->where('polls.0.source', null));
});
