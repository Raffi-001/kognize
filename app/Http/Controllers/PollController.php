<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PollStatus;
use App\Http\Resources\PollResultResource;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PollController
{
    /**
     * Public value questions. Results and the source text are revealed only
     * after answering, so earlier answers do not anchor later ones.
     */
    public function index(#[CurrentUser] User $user): Response
    {
        $polls = Poll::query()
            ->whereIn('status', [PollStatus::Published, PollStatus::Closed])
            ->with([
                'options' => fn (HasMany $query) => $query->withCount('votes'),
                'source.analysis.submission',
                'votes' => fn (HasMany $query) => $query->whereBelongsTo($user),
            ])
            ->orderByRaw('case when status = ? then 0 else 1 end', [PollStatus::Published->value])
            ->latest('published_at')
            ->get();

        return Inertia::render('poll/Index', [
            'polls' => $polls->map(function (Poll $poll): array {
                $vote = $poll->votes->first();
                $revealed = $vote instanceof PollVote || $poll->status === PollStatus::Closed;
                $submission = $poll->source?->analysis->submission;

                return [
                    'id' => $poll->id,
                    'question' => $poll->question,
                    'status' => $poll->status,
                    'is_open' => $poll->status === PollStatus::Published,
                    'options' => $poll->options->map(fn (PollOption $option): array => ['id' => $option->id, 'label' => $option->label])->values(),
                    'my_option_id' => $vote?->poll_option_id,
                    'my_reason' => $vote?->reason,
                    'results' => $revealed ? PollResultResource::make($poll) : null,
                    'source' => $revealed && $submission !== null
                        ? ['submission_id' => $submission->id, 'title' => $submission->title]
                        : null,
                    'published_at_human' => $poll->published_at?->diffForHumans(),
                ];
            })->values(),
        ]);
    }
}
