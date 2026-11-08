<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Http\Resources\AnalysisNoteResource;
use App\Http\Resources\QuestionSummaryResource;
use App\Http\Resources\SubmissionSourceResource;
use App\Models\AnalysisNote;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Factual questions raised by analyses, settled with evidence. Value
 * questions are deliberately absent: they live only as public questions.
 */
final readonly class QuestionController
{
    public function index(Request $request): Response
    {
        $request->validate([
            'status' => ['nullable', Rule::enum(QuestionStatus::class)],
        ]);

        $status = $request->enum('status', QuestionStatus::class) ?? QuestionStatus::Open;

        $questions = AnalysisNote::query()
            ->where('question_kind', QuestionKind::Factual)
            ->where('status', $status)
            ->with('analysis.submission')
            ->withCount(['evidence', 'comments'])
            ->latest($status === QuestionStatus::Resolved ? 'resolved_at' : 'id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('question/Index', [
            'questions' => QuestionSummaryResource::collection($questions),
            'status' => $status,
            'counts' => [
                'open' => $this->count(QuestionStatus::Open),
                'resolved' => $this->count(QuestionStatus::Resolved),
            ],
        ]);
    }

    public function show(AnalysisNote $question, #[CurrentUser] User $user): Response
    {
        abort_unless($question->question_kind === QuestionKind::Factual, 404);

        $mine = fn (MorphMany $query) => $query->where('user_id', $user->id);

        $question->load([
            'analysis.submission',
            'comments.user',
            'comments.votes' => $mine,
            'evidence' => fn (HasMany $query) => $query->reorder()->orderByDesc('score')->latest('id'),
            'evidence.user',
            'evidence.votes' => $mine,
        ]);

        return Inertia::render('question/Show', [
            'question' => AnalysisNoteResource::make($question),
            'source' => SubmissionSourceResource::make($question->analysis->submission),
        ]);
    }

    private function count(QuestionStatus $status): int
    {
        return AnalysisNote::query()
            ->where('question_kind', QuestionKind::Factual)
            ->where('status', $status)
            ->count();
    }
}
