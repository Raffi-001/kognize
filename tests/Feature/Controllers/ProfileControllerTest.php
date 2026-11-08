<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Enums\Badge;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserNote;
use Inertia\Testing\AssertableInertia as Assert;

it('requires authentication', function (): void {
    $this->get(route('profiles.show', User::factory()->create()))->assertRedirectToRoute('login');
});

it('shows reputation, badges, privileges and top notes', function (): void {
    $user = User::factory()->create(['name' => 'Ani', 'created_at' => '2026-10-01']);
    $note = UserNote::factory()->for($user)->create(['text' => 'Useful context']);
    UserNote::factory()->for($user)->create(['score' => 0]);
    $voters = User::factory()->count(5)->create();
    foreach ($voters as $voter) {
        resolve(CastVote::class)->handle($voter, $note, 1);
    }

    UserBadge::factory()->for($user)->create(['badge' => Badge::Supporter]);

    $this->actingAs($voters[0])
        ->get(route('profiles.show', $user))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('profile/Show')
            ->where('profile.name', 'Ani')
            ->where('profile.reputation', 51)
            ->where('profile.member_since', 'October 2026')
            ->where('profile.is_you', false)
            ->where('stats', ['notes' => 2, 'replies' => 0, 'sources' => 0, 'votes' => 0])
            ->where('badges.0', [
                'value' => 'supporter',
                'label' => 'Supporter',
                'description' => 'Cast your first upvote.',
                'tier' => 'bronze',
                'count' => 1,
            ])
            ->where('badges.7.value', 'good_note')
            ->where('badges.7.count', 1)
            ->where('privileges.0.value', 'vote_up')
            ->where('privileges.0.unlocked', true)
            ->where('privileges.1.value', 'vote_down')
            ->where('privileges.1.unlocked', true)
            ->where('privileges.2.unlocked', false)
            ->has('history', 30)
            ->where('history.29.amount', 50)
            ->where('history.0.amount', 0)
            ->has('recentReputation', 5)
            ->where('recentReputation.0.reason', 'Community note upvoted')
            ->where('recentReputation.0.amount', 10)
            ->has('topNotes', 1)
            ->where('topNotes.0.text', 'Useful context')
            ->where('topNotes.0.score', 5)
            ->where('topNotes.0.badge', ['value' => 'good_note', 'tier' => 'bronze'])
            ->where('topNotes.0.submission.id', $note->submission_id));
});

it('knows when you look at your own profile', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profiles.show', $user))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('profile.is_you', true)
            ->where('topNotes', [])
            ->where('recentReputation', []));
});
