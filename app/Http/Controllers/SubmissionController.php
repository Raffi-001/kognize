<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateSubmission;
use App\Enums\QuestionKind;
use App\Enums\SubmissionState;
use App\Enums\UrlSubmissionStatus;
use App\Http\Requests\CreateSubmissionRequest;
use App\Http\Resources\SubmissionAnalysisResource;
use App\Http\Resources\SubmissionResource;
use App\Http\Resources\SubmissionSourceResource;
use App\Http\Resources\UserNoteResource;
use App\Models\AnalysisNote;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use App\Models\UrlSubmission;
use App\Models\User;
use Closure;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SubmissionController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        $request->validate([
            'scope' => ['nullable', Rule::in(['mine', 'community'])],
            'state' => ['nullable', Rule::enum(SubmissionState::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $scope = $request->string('scope', 'mine')->value();
        $state = $request->enum('state', SubmissionState::class);
        $search = $request->filled('search') ? $request->string('search')->trim()->value() : null;

        $submissions = Submission::query()
            ->with(['user', 'analyses' => fn (HasMany $query) => $query->withCount(['notes' => $this->shownWithArticle()])])
            ->when($scope === 'mine', fn (Builder $query) => $query->whereBelongsTo($user))
            ->when($state, fn (Builder $query, SubmissionState $state) => $query->where('state', $state))
            ->when($search, fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query->whereLike('title', sprintf('%%%s%%', $search))->orWhereLike('body', sprintf('%%%s%%', $search)),
            ))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('submission/Index', [
            'submissions' => SubmissionResource::collection($submissions),
            'filters' => [
                'scope' => $scope,
                'state' => $state,
                'search' => $search,
            ],
            'states' => array_map(fn (SubmissionState $state): array => [
                'value' => $state->value,
                'label' => $state->label(),
            ], SubmissionState::cases()),
        ]);
    }

    public function create(Request $request, #[CurrentUser] User $user): Response
    {
        $request->validate([
            'submission' => ['nullable', 'integer'],
            'link' => ['nullable', 'integer'],
        ]);

        return Inertia::render('submission/Create', [
            'sampleText' => config()->string('analysis.sample_text'),
            'wordLimits' => [
                'min' => config()->integer('analysis.min_words'),
                'max' => config()->integer('analysis.max_words'),
            ],
            'recentSubmissions' => SubmissionResource::collection(
                $user->submissions()
                    ->with(['user', 'analyses' => fn (HasMany $query) => $query->withCount(['notes' => $this->shownWithArticle()])])
                    ->latest('id')
                    ->limit(5)
                    ->get(),
            ),
            'progress' => $this->progress(
                $request->filled('link') ? $user->urlSubmissions()->with('submission')->find($request->integer('link')) : null,
                $request->filled('submission') ? $user->submissions()->find($request->integer('submission')) : null,
            ),
        ]);
    }

    public function store(CreateSubmissionRequest $request, #[CurrentUser] User $user, CreateSubmission $action): RedirectResponse
    {
        $submission = $action->handle(
            $user,
            $request->string('body')->value(),
            $request->filled('source') ? $request->string('source')->value() : null,
        );

        return to_route('dashboard', ['submission' => $submission->id]);
    }

    public function show(Submission $submission, #[CurrentUser] User $user): Response
    {
        $mine = fn (MorphMany $query) => $query->where('user_id', $user->id);

        $submission->load([
            'user',
            'analyses' => fn (HasMany $query) => $query->withCount(['notes' => $this->shownWithArticle()]),
            'analyses.notes' => $this->shownWithArticle(),
            'analyses.notes.comments.user',
            'analyses.notes.comments.votes' => $mine,
            'analyses.notes.evidence.user',
            'analyses.notes.evidence.votes' => $mine,
            'userNotes' => fn (HasMany $query) => $query->orderByDesc('score')->latest('id'),
            'userNotes.user',
            'userNotes.votes' => $mine,
            'userNotes.comments.user',
            'userNotes.comments.votes' => $mine,
        ]);

        return Inertia::render('submission/Show', [
            'submission' => SubmissionResource::make($submission),
            'body' => $submission->body,
            'argumentMap' => $submission->argument_map,
            'analyses' => SubmissionAnalysisResource::collection($submission->analyses),
            'userNotes' => UserNoteResource::collection($submission->userNotes),
        ]);
    }

    /**
     * Where the analysis the user just started stands, so the composer can
     * follow it until the result is ready.
     *
     * @return array<string, mixed>|null
     */
    private function progress(?UrlSubmission $link, ?Submission $submission): ?array
    {
        $submission = $link instanceof UrlSubmission ? $link->submission : $submission;

        if (! $link instanceof UrlSubmission && ! $submission instanceof Submission) {
            return null;
        }

        $stage = match (true) {
            $link?->status === UrlSubmissionStatus::Pending => 'queued',
            $link?->status === UrlSubmissionStatus::Processing => 'fetching',
            $link?->status === UrlSubmissionStatus::Failed, $submission?->state === SubmissionState::Failed => 'failed',
            $submission?->state === SubmissionState::Analyzed => 'done',
            default => 'analyzing',
        };

        return [
            'source' => $link instanceof UrlSubmission ? 'url' : 'text',
            'url' => $link?->url,
            'stage' => $stage,
            'is_in_progress' => ! in_array($stage, ['done', 'failed'], true),
            'submission_id' => $submission?->id,
            'title' => $submission === null ? null : SubmissionSourceResource::make($submission)->resolve()['title'],
            'error' => $link?->error_message,
        ];
    }

    /**
     * Value questions are only ever shown as anonymous public questions, so
     * they are never loaded or counted together with the article they came from.
     *
     * @return Closure(Builder<AnalysisNote>|HasMany<AnalysisNote, SubmissionAnalysis>): void
     */
    private function shownWithArticle(): Closure
    {
        return function (Builder|HasMany $query): void {
            $query->where(fn (Builder $query) => $query
                ->whereNull('question_kind')
                ->orWhere('question_kind', '!=', QuestionKind::Value));
        };
    }
}
