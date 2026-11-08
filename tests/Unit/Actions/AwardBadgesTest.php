<?php

declare(strict_types=1);

use App\Actions\AwardBadges;
use App\Enums\Badge;
use App\Enums\EvidenceVerification;
use App\Models\Comment;
use App\Models\Evidence;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserNote;
use App\Models\Vote;

function badgesOf(User $user): array
{
    return $user->badges()->get()->map(fn (UserBadge $badge): string => $badge->badge->value)->sort()->values()->all();
}

it('awards nothing to a newcomer', function (): void {
    $user = User::factory()->create();

    resolve(AwardBadges::class)->handle($user);

    expect(badgesOf($user))->toBe([]);
});

it('awards badges for activity, once', function (): void {
    $user = User::factory()->create(['reputation' => 2000]);
    Vote::factory()->for($user)->create(['value' => 1]);
    Vote::factory()->for($user)->create(['value' => -1]);
    UserNote::factory()->for($user)->create();
    Comment::factory()->for($user)->count(10)->create();
    Evidence::factory()->for($user)->count(10)->create(['verification' => EvidenceVerification::Verified]);
    User::factory()->count(5)->create(['referrer_id' => $user->id]);

    resolve(AwardBadges::class)->handle($user);
    resolve(AwardBadges::class)->handle($user);

    expect(badgesOf($user))->toBe([
        'annotator', 'commentator', 'critic', 'fact_checker', 'pillar',
        'recruiter', 'researcher', 'respected', 'supporter',
    ])->and($user->notifications()->where('type', 'badge_earned')->count())->toBe(9);
});

it('awards the researcher badge before the fact checker badge', function (): void {
    $user = User::factory()->create(['reputation' => 499]);
    Evidence::factory()->for($user)->create(['verification' => EvidenceVerification::Verified]);
    Evidence::factory()->for($user)->count(9)->create(['verification' => EvidenceVerification::NotFound]);
    Comment::factory()->for($user)->count(9)->create();
    User::factory()->count(4)->create(['referrer_id' => $user->id]);

    resolve(AwardBadges::class)->handle($user);

    expect(badgesOf($user))->toBe(['researcher']);
});

it('awards note badges for every note that scores high enough', function (): void {
    $user = User::factory()->create();
    $great = UserNote::factory()->for($user)->create(['score' => 15]);
    $good = UserNote::factory()->for($user)->create(['score' => 5]);
    UserNote::factory()->for($user)->create(['score' => 4]);

    resolve(AwardBadges::class)->handle($user);
    resolve(AwardBadges::class)->handle($user);

    $noteBadges = $user->badges()->whereNotNull('subject_id')->get()
        ->map(fn (UserBadge $badge): array => [$badge->badge, $badge->subject_id])
        ->sortBy(fn (array $pair): string => $pair[0]->value.$pair[1])
        ->values()
        ->all();

    expect($noteBadges)->toBe([
        [Badge::GoodNote, min($great->id, $good->id)],
        [Badge::GoodNote, max($great->id, $good->id)],
        [Badge::GreatNote, $great->id],
    ]);
});
