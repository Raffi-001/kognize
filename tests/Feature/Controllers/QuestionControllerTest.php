<?php

declare(strict_types=1);

use App\Enums\EvidenceVerification;
use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Enums\Verdict;
use App\Models\AnalysisNote;
use App\Models\Comment;
use App\Models\Evidence;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use App\Models\User;
use App\Models\Vote;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();
});

it('requires authentication', function (string $route): void {
    $question = AnalysisNote::factory()->question()->create();

    $this->get(route($route, ['question' => $question]))
        ->assertRedirectToRoute('login');
})->with(['questions.index', 'questions.show']);

it('lists the unresolved factual questions, newest first', function (): void {
    $submission = Submission::factory()->create([
        'title' => 'Հոդված',
        'source' => 'https://www.news.am/article',
    ]);
    $analysis = SubmissionAnalysis::factory()->for($submission)->create();

    $older = AnalysisNote::factory()->question()->create([
        'submission_analysis_id' => $analysis->id,
        'label' => 'Մեջբերում',
        'created_at' => now()->subDay(),
    ]);
    Evidence::factory()->for($older, 'question')->create();
    Comment::factory()->for($older, 'commentable')->count(2)->create();
    $newer = AnalysisNote::factory()->question()->create();

    AnalysisNote::factory()->question()->create(['status' => QuestionStatus::Resolved, 'resolved_at' => now()]);
    AnalysisNote::factory()->question(QuestionKind::Value)->create();
    AnalysisNote::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('questions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('question/Index', false)
            ->where('status', 'open')
            ->where('counts', ['open' => 2, 'resolved' => 1])
            ->has('questions.data', 2)
            ->where('questions.data.0.id', $newer->id)
            ->where('questions.data.1.id', $older->id)
            ->where('questions.data.1.question', $older->aspect_label)
            ->where('questions.data.1.passage', 'Մեջբերում')
            ->where('questions.data.1.status', 'open')
            ->where('questions.data.1.verdict', null)
            ->where('questions.data.1.verdict_label', null)
            ->where('questions.data.1.evidence_count', 1)
            ->where('questions.data.1.comments_count', 2)
            ->where('questions.data.1.source', ['id' => $submission->id, 'title' => 'Հոդված', 'source_host' => 'news.am'])
            ->where('questions.data.1.created_at_human', '1 day ago')
            ->where('questions.data.1.resolved_at_human', null)
            ->has('questions.meta'));
});

it('lists the resolved factual questions, most recently resolved first', function (): void {
    $earlier = AnalysisNote::factory()->question()->create([
        'status' => QuestionStatus::Resolved,
        'verdict' => Verdict::Supported,
        'resolved_at' => now()->subWeek(),
    ]);
    $later = AnalysisNote::factory()->question()->create([
        'status' => QuestionStatus::Resolved,
        'verdict' => Verdict::Refuted,
        'resolved_at' => now()->subHour(),
    ]);
    AnalysisNote::factory()->question()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('questions.index', ['status' => 'resolved']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('status', 'resolved')
            ->has('questions.data', 2)
            ->where('questions.data.0.id', $later->id)
            ->where('questions.data.0.verdict', 'refuted')
            ->where('questions.data.0.verdict_label', 'Refuted by evidence')
            ->where('questions.data.0.resolved_at_human', '1 hour ago')
            ->where('questions.data.1.id', $earlier->id));
});

it('falls back to the text when a question has no short form', function (): void {
    $question = AnalysisNote::factory()->question()->create(['aspect_label' => null, 'text' => 'Ամբողջական հարց']);

    $this->actingAs(User::factory()->create())
        ->get(route('questions.index'))
        ->assertInertia(fn ($page) => $page
            ->where('questions.data.0.id', $question->id)
            ->where('questions.data.0.question', 'Ամբողջական հարց'));
});

it('validates the status filter', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('questions.index', ['status' => 'unknown']))
        ->assertSessionHasErrors('status');
});

it('renders the page of a factual question', function (): void {
    $user = User::factory()->create(['name' => 'Anna']);
    $submission = Submission::factory()->create(['title' => null, 'body' => 'Առաջին երկրորդ երրորդ', 'source' => null]);
    $analysis = SubmissionAnalysis::factory()->for($submission)->create();
    $question = AnalysisNote::factory()->question()->create([
        'submission_analysis_id' => $analysis->id,
        'label' => 'Մեջբերում',
    ]);
    $evidence = Evidence::factory()->for($question, 'question')->for($user)->create(['verification' => EvidenceVerification::Pending]);
    $comment = Comment::factory()->for($question, 'commentable')->for($user)->create(['text' => 'Լավ հարց']);

    $this->actingAs($user)
        ->get(route('questions.show', $question))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('question/Show', false)
            ->where('question.id', $question->id)
            ->where('question.label', 'Մեջբերում')
            ->where('question.question_kind', 'factual')
            ->where('question.status', 'open')
            ->where('question.evidence.0.id', $evidence->id)
            ->where('question.evidence.0.verification', 'pending')
            ->where('question.comments.0.id', $comment->id)
            ->where('question.comments.0.author', ['id' => $user->id, 'name' => 'Anna', 'reputation' => 1])
            ->where('source', ['id' => $submission->id, 'title' => 'Առաջին երկրորդ երրորդ', 'source_host' => null]));
});

it('does not give value questions or other findings a page', function (?QuestionKind $kind): void {
    $note = $kind instanceof QuestionKind
        ? AnalysisNote::factory()->question($kind)->create()
        : AnalysisNote::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('questions.show', $note))
        ->assertNotFound();
})->with([
    'value question' => [QuestionKind::Value],
    'finding' => [null],
]);

it('lists the best sources first and shows how the viewer voted', function (): void {
    $viewer = User::factory()->create();
    $question = AnalysisNote::factory()->question()->create();
    $first = Evidence::factory()->for($question, 'question')->create(['score' => 0]);
    $best = Evidence::factory()->for($question, 'question')->create(['score' => 4]);
    Vote::factory()->for($viewer)->create(['votable_type' => 'evidence', 'votable_id' => $best->id, 'value' => 1]);

    $this->actingAs($viewer)
        ->get(route('questions.show', $question))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('question.evidence.0.id', $best->id)
            ->where('question.evidence.0.my_vote', 1)
            ->where('question.evidence.1.id', $first->id));
});
