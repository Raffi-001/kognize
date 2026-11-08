<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Badge;
use App\Enums\EvidenceVerification;
use App\Enums\NotificationKind;
use App\Models\Comment;
use App\Models\Evidence;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserNote;

/**
 * Awards every badge the user has earned but not received yet. Badges are
 * never taken away.
 */
final readonly class AwardBadges
{
    public function __construct(private NotifyActivity $notify) {}

    public function handle(User $user): void
    {
        $earned = $user->badges()->get();

        foreach (Badge::cases() as $badge) {
            if ($badge->isPerNote()) {
                UserNote::query()
                    ->whereBelongsTo($user)
                    ->where('score', '>=', $badge->noteScore())
                    ->whereNotIn('id', $earned->where('badge', $badge)->pluck('subject_id')->filter()->all())
                    ->each(fn (UserNote $note) => $this->award($user, $badge, $note));

                continue;
            }

            if ($earned->doesntContain('badge', $badge) && $this->qualifies($user, $badge)) {
                $this->award($user, $badge);
            }
        }
    }

    private function qualifies(User $user, Badge $badge): bool
    {
        return match ($badge) {
            Badge::Supporter => $user->votes()->where('value', 1)->exists(),
            Badge::Critic => $user->votes()->where('value', -1)->exists(),
            Badge::Annotator => UserNote::query()->whereBelongsTo($user)->exists(),
            Badge::Commentator => Comment::query()->whereBelongsTo($user)->count() >= 10,
            Badge::Researcher => $this->verifiedSources($user) >= 1,
            Badge::FactChecker => $this->verifiedSources($user) >= 10,
            Badge::Recruiter => $user->referees()->count() >= 5,
            Badge::Respected => $user->reputation >= 500,
            Badge::Pillar => $user->reputation >= 2000,
            default => false,
        };
    }

    private function verifiedSources(User $user): int
    {
        return Evidence::query()
            ->whereBelongsTo($user)
            ->where('verification', EvidenceVerification::Verified)
            ->count();
    }

    private function award(User $user, Badge $badge, ?UserNote $note = null): void
    {
        UserBadge::query()->create([
            'user_id' => $user->id,
            'badge' => $badge,
            'subject_type' => $note?->getMorphClass(),
            'subject_id' => $note?->id,
        ]);

        $this->notify->handle(
            [$user],
            NotificationKind::BadgeEarned,
            null,
            ['type' => 'badge', 'id' => $badge->value, 'title' => $badge->label()],
            route('profiles.show', $user),
            $badge->description(),
        );
    }
}
