<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\PollStatus;
use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Enums\SubmissionState;
use App\Http\Resources\NotificationResource;
use App\Models\AnalysisNote;
use App\Models\Poll;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'usage' => $user instanceof User ? fn (): array => $this->usage($user) : null,
            ],
            'openPollsCount' => $user instanceof User ? fn (): int => Poll::query()
                ->where('status', PollStatus::Published)
                ->whereDoesntHave('votes', fn (Builder $query) => $query->whereBelongsTo($user))
                ->count() : 0,
            'openQuestionsCount' => $user instanceof User ? fn (): int => AnalysisNote::query()
                ->where('question_kind', QuestionKind::Factual)
                ->where('status', QuestionStatus::Open)
                ->count() : 0,
            'unreadNotificationsCount' => $user instanceof User ? fn (): int => $user->unreadNotifications()->count() : 0,
            'latestNotifications' => Inertia::optional(fn (): array => $user instanceof User
                ? NotificationResource::collection($user->notifications()->limit(8)->get())->resolve($request)
                : []),
            'adminUrl' => $user instanceof User && $user->is_admin ? url('/admin') : null,
            'recentSubmissions' => $user instanceof User ? fn (): array => $this->recentSubmissions($user) : [],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array<int, array{id: int, title: string, state: SubmissionState}>
     */
    private function recentSubmissions(User $user): array
    {
        return $user->submissions()
            ->latest('id')
            ->limit(3)
            ->get(['id', 'title', 'body', 'state'])
            ->map(fn (Submission $submission): array => [
                'id' => $submission->id,
                'title' => $submission->title ?? Str::words(Str::squish($submission->body), 8),
                'state' => $submission->state,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function usage(User $user): array
    {
        $dailySubmissions = $user->plan->dailySubmissions();
        $remainingSubmissions = $user->remainingSubmissionsToday();
        $invitations = $user->plan->invitations();
        $remainingInvitations = $user->remainingInvitations();

        return [
            'plan' => $user->plan,
            'plan_label' => $user->plan->label(),
            'submissions' => [
                'limit' => $dailySubmissions,
                'used' => $dailySubmissions - $remainingSubmissions,
                'remaining' => $remainingSubmissions,
            ],
            'invitations' => [
                'limit' => $invitations,
                'used' => $invitations - $remainingInvitations,
                'remaining' => $remainingInvitations,
            ],
        ];
    }
}
