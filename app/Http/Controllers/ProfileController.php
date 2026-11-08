<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Badge;
use App\Enums\BadgeTier;
use App\Enums\EvidenceVerification;
use App\Enums\Privilege;
use App\Models\Comment;
use App\Models\Evidence;
use App\Models\ReputationEvent;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserNote;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ProfileController
{
    private const int HISTORY_DAYS = 30;

    public function show(User $user, #[CurrentUser] User $viewer): Response
    {
        $badges = $user->badges()->get();

        return Inertia::render('profile/Show', [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'reputation' => $user->reputation,
                'member_since' => $user->created_at->format('F Y'),
                'is_you' => $user->is($viewer),
            ],
            'stats' => [
                'notes' => UserNote::query()->whereBelongsTo($user)->count(),
                'replies' => Comment::query()->whereBelongsTo($user)->count(),
                'sources' => Evidence::query()->whereBelongsTo($user)->where('verification', EvidenceVerification::Verified)->count(),
                'votes' => $user->votes()->count(),
            ],
            'badges' => array_map(fn (Badge $badge): array => [
                'value' => $badge->value,
                'label' => $badge->label(),
                'description' => $badge->description(),
                'tier' => $badge->tier(),
                'count' => $badges->where('badge', $badge)->count(),
            ], Badge::cases()),
            'privileges' => array_map(fn (Privilege $privilege): array => [
                'value' => $privilege->value,
                'label' => $privilege->label(),
                'description' => $privilege->description(),
                'threshold' => $privilege->threshold(),
                'unlocked' => $user->hasPrivilege($privilege),
            ], Privilege::ladder()),
            'history' => $this->history($user),
            'recentReputation' => $user->reputationEvents()
                ->latest('id')
                ->limit(10)
                ->get()
                ->map(fn (ReputationEvent $event): array => [
                    'id' => $event->id,
                    'reason' => $event->reason->getLabel(),
                    'amount' => $event->amount,
                    'created_at_human' => $event->created_at->diffForHumans(),
                ])
                ->all(),
            'topNotes' => UserNote::query()
                ->whereBelongsTo($user)
                ->where('score', '>', 0)
                ->with('submission')
                ->orderByDesc('score')
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (UserNote $note): array => [
                    'id' => $note->id,
                    'text' => $note->text,
                    'score' => $note->score,
                    'submission' => ['id' => $note->submission->id, 'title' => $note->submission->displayTitle()],
                    'badge' => $this->bestNoteBadge($badges, $note),
                ])
                ->all(),
        ]);
    }

    /**
     * The highest badge a community note earned.
     *
     * @param  EloquentCollection<int, UserBadge>  $badges
     * @return array{value: Badge, tier: BadgeTier}|null
     */
    private function bestNoteBadge(EloquentCollection $badges, UserNote $note): ?array
    {
        $best = $badges
            ->where('subject_type', 'user_note')
            ->where('subject_id', $note->id)
            ->sortByDesc(fn (UserBadge $badge): int => $badge->badge->noteScore())
            ->first();

        return $best instanceof UserBadge ? ['value' => $best->badge, 'tier' => $best->badge->tier()] : null;
    }

    /**
     * Reputation gained or lost on each of the last days, oldest first.
     *
     * @return list<array{date: string, amount: int}>
     */
    private function history(User $user): array
    {
        $since = today()->subDays(self::HISTORY_DAYS - 1);
        $totals = $user->reputationEvents()
            ->where('created_at', '>=', $since)
            ->get(['amount', 'created_at'])
            ->groupBy(fn (ReputationEvent $event): string => $event->created_at->toDateString())
            ->map(fn (Collection $events): int => array_sum($events->map(fn (ReputationEvent $event): int => $event->amount)->all()));

        return array_map(function (int $offset) use ($since, $totals): array {
            $date = $since->copy()->addDays($offset)->toDateString();

            return ['date' => $date, 'amount' => $totals->get($date, 0)];
        }, range(0, self::HISTORY_DAYS - 1));
    }
}
