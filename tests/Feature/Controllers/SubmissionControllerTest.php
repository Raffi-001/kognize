<?php

declare(strict_types=1);

use App\Enums\AnalysisType;
use App\Enums\EvidenceAssessment;
use App\Enums\Plan;
use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Enums\Stance;
use App\Enums\SubmissionKind;
use App\Enums\SubmissionState;
use App\Enums\UrlSubmissionStatus;
use App\Enums\Verdict;
use App\Models\AnalysisNote;
use App\Models\Comment;
use App\Models\Evidence;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use App\Models\UrlSubmission;
use App\Models\User;
use App\Models\UserNote;
use App\Models\Vote;
use App\Rules\ArmenianText;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();
});

it('requires authentication', function (string $route): void {
    $submission = Submission::factory()->create();

    $this->get(route($route, $submission))
        ->assertRedirectToRoute('login');
})->with(['dashboard', 'submissions.index', 'submissions.show']);

it('renders the dashboard', function (): void {
    $user = User::factory()->create();

    Submission::factory()->for($user)->count(6)->create();
    Submission::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('submission/Create', false)
            ->where('sampleText', config('analysis.sample_text'))
            ->where('wordLimits', ['min' => config('analysis.min_words'), 'max' => config('analysis.max_words')])
            ->has('recentSubmissions', 5)
            ->where('recentSubmissions.0.is_owner', true)
            ->where('progress', null)
            ->where('auth.usage.plan', 'standard'));
});

it('follows a text analysis the user started', function (SubmissionState $state, string $stage, bool $inProgress): void {
    $user = User::factory()->create();
    $submission = Submission::factory()->for($user)->create(['state' => $state, 'title' => 'Հոդված']);

    $this->actingAs($user)
        ->get(route('dashboard', ['submission' => $submission->id]))
        ->assertInertia(fn ($page) => $page->where('progress', [
            'source' => 'text',
            'url' => null,
            'stage' => $stage,
            'is_in_progress' => $inProgress,
            'submission_id' => $submission->id,
            'title' => 'Հոդված',
            'error' => null,
        ]));
})->with([
    'pending' => [SubmissionState::Pending, 'analyzing', true],
    'processing' => [SubmissionState::Processing, 'analyzing', true],
    'analyzed' => [SubmissionState::Analyzed, 'done', false],
    'failed' => [SubmissionState::Failed, 'failed', false],
]);

it('follows a link from fetching to the finished analysis', function (UrlSubmissionStatus $status, ?SubmissionState $state, string $stage): void {
    $user = User::factory()->create();
    $submission = $state instanceof SubmissionState
        ? Submission::factory()->for($user)->create(['state' => $state, 'title' => null, 'body' => 'Առաջին երկրորդ'])
        : null;
    $link = UrlSubmission::factory()->for($user)->create([
        'url' => 'https://news.am/article',
        'status' => $status,
        'submission_id' => $submission?->id,
        'error_message' => $status === UrlSubmissionStatus::Failed ? 'Could not open the page.' : null,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['link' => $link->id]))
        ->assertInertia(fn ($page) => $page
            ->where('progress.source', 'url')
            ->where('progress.url', 'https://news.am/article')
            ->where('progress.stage', $stage)
            ->where('progress.submission_id', $submission?->id)
            ->where('progress.title', $submission instanceof Submission ? 'Առաջին երկրորդ' : null)
            ->where('progress.error', $status === UrlSubmissionStatus::Failed ? 'Could not open the page.' : null));
})->with([
    'queued' => [UrlSubmissionStatus::Pending, null, 'queued'],
    'fetching' => [UrlSubmissionStatus::Processing, null, 'fetching'],
    'fetch failed' => [UrlSubmissionStatus::Failed, null, 'failed'],
    'analyzing' => [UrlSubmissionStatus::Completed, SubmissionState::Processing, 'analyzing'],
    'done' => [UrlSubmissionStatus::Completed, SubmissionState::Analyzed, 'done'],
]);

it('does not follow the work of other users', function (): void {
    $submission = Submission::factory()->create();
    $link = UrlSubmission::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard', ['submission' => $submission->id, 'link' => $link->id]))
        ->assertInertia(fn ($page) => $page->where('progress', null));
});

it('validates what the dashboard follows', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard', ['submission' => 'abc']))
        ->assertSessionHasErrors('submission');
});

it('lists the submissions of the user by default', function (): void {
    $user = User::factory()->create(['name' => 'Anna']);

    $submission = Submission::factory()->for($user)->analyzed()->create([
        'title' => null,
        'source' => 'https://www.example.com/article',
    ]);
    SubmissionAnalysis::factory()->for($submission)->has(AnalysisNote::factory()->count(2), 'notes')->create(['type' => AnalysisType::Fallacy]);
    SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Rhetoric]);
    Submission::factory()->create();

    $response = $this->actingAs($user)->get(route('submissions.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('submission/Index', false)
            ->has('submissions.data', 1)
            ->where('submissions.data.0.id', $submission->id)
            ->where('submissions.data.0.title', Str::words(Str::squish($submission->body), 14))
            ->where('submissions.data.0.source', 'https://www.example.com/article')
            ->where('submissions.data.0.source_host', 'example.com')
            ->where('submissions.data.0.state', 'analyzed')
            ->where('submissions.data.0.state_label', 'Analyzed')
            ->where('submissions.data.0.is_in_progress', false)
            ->where('submissions.data.0.counts', ['fallacy' => 2, 'rhetoric' => 0, 'questions' => 0])
            ->where('submissions.data.0.word_count', ArmenianText::wordCount($submission->body))
            ->where('submissions.data.0.author', ['id' => $user->id, 'name' => 'Anna', 'reputation' => 1])
            ->where('submissions.data.0.is_owner', true)
            ->has('submissions.data.0.excerpt')
            ->has('submissions.data.0.created_at')
            ->has('submissions.data.0.created_at_human')
            ->where('filters', ['scope' => 'mine', 'state' => null, 'search' => null])
            ->has('states', count(SubmissionState::cases()))
            ->where('states.0', ['value' => 'pending', 'label' => 'Queued']));
});

it('lists community submissions', function (): void {
    $user = User::factory()->create();

    Submission::factory()->for($user)->create();
    $other = Submission::factory()->create(['source' => null]);

    $response = $this->actingAs($user)->get(route('submissions.index', ['scope' => 'community']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('submissions.data', 2)
            ->where('submissions.data.0.id', $other->id)
            ->where('submissions.data.0.is_owner', false)
            ->where('submissions.data.0.source_host', null)
            ->where('filters.scope', 'community'));
});

it('filters submissions by state and search', function (): void {
    $user = User::factory()->create();

    $match = Submission::factory()->for($user)->analyzed()->create(['title' => 'Needle in a haystack']);
    $bodyMatch = Submission::factory()->for($user)->analyzed()->create(['title' => 'Other', 'body' => 'Some needle body']);
    Submission::factory()->for($user)->create(['title' => 'Needle but pending']);
    Submission::factory()->for($user)->analyzed()->create(['title' => 'Nothing']);

    $response = $this->actingAs($user)->get(route('submissions.index', ['state' => 'analyzed', 'search' => ' needle ']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('submissions.data', 2)
            ->where('submissions.data.0.id', $bodyMatch->id)
            ->where('submissions.data.1.id', $match->id)
            ->where('filters', ['scope' => 'mine', 'state' => 'analyzed', 'search' => 'needle']));
});

it('validates the filters', function (array $query, string $error): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->fromRoute('dashboard')
        ->get(route('submissions.index', $query))
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHasErrors($error);
})->with([
    [['scope' => 'everyone'], 'scope'],
    [['state' => 'unknown'], 'state'],
    [['search' => str_repeat('a', 101)], 'search'],
]);

it('may create a submission', function (): void {
    Bus::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->post(route('submissions.store'), [
            'body' => config('analysis.sample_text'),
            'source' => 'https://example.com/article',
        ]);

    $submission = Submission::query()->sole();

    $response->assertRedirectToRoute('dashboard', ['submission' => $submission->id]);

    expect($submission->user->is($user))->toBeTrue()
        ->and($submission->body)->toBe(config('analysis.sample_text'))
        ->and($submission->source)->toBe('https://example.com/article');

    Bus::assertBatched(fn (PendingBatch $batch): bool => $batch->jobs->count() === 1);
});

it('may create a submission without a source', function (): void {
    Bus::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->fromRoute('dashboard')
        ->post(route('submissions.store'), [
            'body' => config('analysis.sample_text'),
            'source' => '',
        ])
        ->assertSessionHasNoErrors();

    expect(Submission::query()->sole()->source)->toBeNull();
});

it('validates the submission', function (array $data, string $error): void {
    Bus::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->fromRoute('dashboard')
        ->post(route('submissions.store'), $data)
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHasErrors($error);

    expect(Submission::query()->count())->toBe(0);
})->with([
    'missing body' => [[], 'body'],
    'not armenian' => [['body' => str_repeat('This is English text. ', 20)], 'body'],
    'invalid source' => [['body' => 'Բարև', 'source' => 'ftp://example.com'], 'source'],
]);

it('limits the daily submissions', function (): void {
    Bus::fake();

    $user = User::factory()->create(['plan' => Plan::Standard]);

    Submission::factory()->for($user)->count(Plan::Standard->dailySubmissions())->create();

    $this->actingAs($user)
        ->fromRoute('dashboard')
        ->post(route('submissions.store'), ['body' => config('analysis.sample_text')])
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHasErrors(['limit' => 'You have used all of your analyses for today.']);

    expect(Submission::query()->count())->toBe(Plan::Standard->dailySubmissions());
});

it('renders a submission', function (): void {
    $user = User::factory()->create();
    $submission = Submission::factory()->analyzed()->create();

    $analysis = SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Rhetoric]);
    $note = AnalysisNote::factory()->for($analysis, 'analysis')->create(['annotation_id' => 'a1', 'strength' => 7]);
    $comment = Comment::factory()->for($note, 'commentable')->for($user)->create(['text' => 'Good point']);

    $userNote = UserNote::factory()->for($submission)->for($user)->create(['label' => 'Phrase', 'text' => 'My note']);
    Comment::factory()->for($userNote, 'commentable')->create(['text' => 'Reply']);

    $response = $this->actingAs($user)->get(route('submissions.show', $submission));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('submission/Show', false)
            ->where('submission.id', $submission->id)
            ->where('submission.is_owner', false)
            ->where('submission.counts.rhetoric', 1)
            ->where('submission.kind', null)
            ->where('submission.kind_label', null)
            ->where('body', $submission->body)
            ->where('argumentMap', null)
            ->has('analyses', 1)
            ->where('analyses.0.id', $analysis->id)
            ->where('analyses.0.type', 'rhetoric')
            ->where('analyses.0.label', 'Rhetoric')
            ->where('analyses.0.status', 'completed')
            ->has('analyses.0.notes', 1)
            ->where('analyses.0.notes.0.id', $note->id)
            ->where('analyses.0.notes.0.annotation_id', 'a1')
            ->where('analyses.0.notes.0.label', $note->label)
            ->where('analyses.0.notes.0.aspect_label', $note->aspect_label)
            ->where('analyses.0.notes.0.text', $note->text)
            ->where('analyses.0.notes.0.ethics', 'questionable')
            ->where('analyses.0.notes.0.strength', 7)
            ->has('analyses.0.notes.0.comments', 1)
            ->where('analyses.0.notes.0.comments.0.id', $comment->id)
            ->where('analyses.0.notes.0.comments.0.text', 'Good point')
            ->where('analyses.0.notes.0.comments.0.author', ['id' => $user->id, 'name' => $user->name, 'reputation' => 1])
            ->where('analyses.0.notes.0.comments.0.is_owner', true)
            ->has('analyses.0.notes.0.comments.0.created_at_human')
            ->where('analyses.0.notes.0.question_kind', null)
            ->where('analyses.0.notes.0.evidence', [])
            ->missing('analyses.0.notes.0.poll')
            ->has('userNotes', 1)
            ->where('userNotes.0.id', $userNote->id)
            ->where('userNotes.0.annotation_id', $userNote->annotation_id)
            ->where('userNotes.0.label', 'Phrase')
            ->where('userNotes.0.text', 'My note')
            ->where('userNotes.0.author', ['id' => $user->id, 'name' => $user->name, 'reputation' => 1])
            ->where('userNotes.0.is_owner', true)
            ->has('userNotes.0.created_at_human')
            ->where('userNotes.0.comments.0.text', 'Reply')
            ->where('userNotes.0.comments.0.is_owner', false));
});

it('renders the argument map and the factual questions of a submission without its value questions', function (): void {
    $user = User::factory()->create(['name' => 'Anna']);
    $moderator = User::factory()->admin()->create();
    $submission = Submission::factory()->analyzed()->create([
        'kind' => SubmissionKind::Argument,
        'argument_map' => ['text_kind' => 'argument', 'claims' => []],
    ]);
    $analysis = SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Questions]);

    $factual = AnalysisNote::factory()->question()->create([
        'submission_analysis_id' => $analysis->id,
        'status' => QuestionStatus::Resolved,
        'verdict' => Verdict::Refuted,
        'resolution' => 'Հերքված է։',
        'resolved_by' => $moderator->id,
        'resolved_at' => now()->subHour(),
    ]);
    $evidence = Evidence::factory()->for($factual, 'question')->for($user)->verified(EvidenceAssessment::Refutes)->create([
        'url' => 'https://www.example.com/report',
        'stance' => Stance::Refutes,
    ]);
    Evidence::factory()->for($factual, 'question')->create(['url' => 'not-a-url']);

    $value = AnalysisNote::factory()->question(QuestionKind::Value)->create(['submission_analysis_id' => $analysis->id]);
    $poll = Poll::factory()->published()->withOptions(['Այո', 'Ոչ'])->create(['analysis_note_id' => $value->id, 'question' => 'Հարց']);
    PollVote::factory()->create(['poll_option_id' => $poll->options[0]->id]);

    $draft = AnalysisNote::factory()->question(QuestionKind::Value)->create(['submission_analysis_id' => $analysis->id]);
    Poll::factory()->withOptions()->create(['analysis_note_id' => $draft->id]);

    $this->actingAs($user)
        ->get(route('submissions.show', $submission))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('submission.kind', 'argument')
            ->where('submission.counts.questions', 1)
            ->where('submission.kind_label', 'Argument')
            ->where('argumentMap', ['text_kind' => 'argument', 'claims' => []])
            ->has('analyses.0.notes', 1)
            ->where('analyses.0.notes.0.question_kind', 'factual')
            ->where('analyses.0.notes.0.status', 'resolved')
            ->where('analyses.0.notes.0.verdict', 'refuted')
            ->where('analyses.0.notes.0.verdict_label', 'Refuted by evidence')
            ->where('analyses.0.notes.0.resolution', 'Հերքված է։')
            ->where('analyses.0.notes.0.resolved_at_human', '1 hour ago')
            ->has('analyses.0.notes.0.evidence', 2)
            ->where('analyses.0.notes.0.evidence.0.source_host', 'not-a-url')
            ->where('analyses.0.notes.0.evidence.1.id', $evidence->id)
            ->where('analyses.0.notes.0.evidence.1.stance', 'refutes')
            ->where('analyses.0.notes.0.evidence.1.url', 'https://www.example.com/report')
            ->where('analyses.0.notes.0.evidence.1.source_host', 'example.com')
            ->where('analyses.0.notes.0.evidence.1.page_title', $evidence->page_title)
            ->where('analyses.0.notes.0.evidence.1.quote', $evidence->quote)
            ->where('analyses.0.notes.0.evidence.1.explanation', null)
            ->where('analyses.0.notes.0.evidence.1.verification', 'verified')
            ->where('analyses.0.notes.0.evidence.1.assessment', 'refutes')
            ->where('analyses.0.notes.0.evidence.1.assessment_reason', $evidence->assessment_reason)
            ->where('analyses.0.notes.0.evidence.1.author', ['id' => $user->id, 'name' => 'Anna', 'reputation' => 1])
            ->has('analyses.0.notes.0.evidence.1.created_at_human'));

});

it('shows scores and how the viewer voted', function (): void {
    $viewer = User::factory()->create();
    $low = UserNote::factory()->create(['score' => 1]);
    $high = UserNote::factory()->for($low->submission)->create(['score' => 9]);
    Vote::factory()->for($viewer)->create(['votable_type' => 'user_note', 'votable_id' => $low->id, 'value' => -1]);

    $this->actingAs($viewer)
        ->get(route('submissions.show', $low->submission))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('userNotes.0.id', $high->id)
            ->where('userNotes.0.my_vote', null)
            ->where('userNotes.1.score', 1)
            ->where('userNotes.1.my_vote', -1));
});
