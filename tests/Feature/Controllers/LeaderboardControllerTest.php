<?php

declare(strict_types=1);

use App\Enums\Badge;
use App\Models\ReputationEvent;
use App\Models\User;
use App\Models\UserBadge;
use Inertia\Testing\AssertableInertia as Assert;

it('requires authentication', function (): void {
    $this->get(route('leaderboard.index'))->assertRedirectToRoute('login');
});

it('ranks people by the reputation they gained this week', function (): void {
    $steady = User::factory()->create(['name' => 'Steady', 'reputation' => 500]);
    ReputationEvent::factory()->for($steady)->create(['amount' => 20]);
    $rising = User::factory()->create(['name' => 'Rising', 'reputation' => 50]);
    ReputationEvent::factory()->for($rising)->create(['amount' => 40]);
    $old = User::factory()->create(['name' => 'Old', 'reputation' => 900]);
    ReputationEvent::factory()->for($old)->create(['amount' => 30, 'created_at' => now()->subWeeks(2)]);
    UserBadge::factory()->for($rising)->create(['badge' => Badge::Pillar]);
    UserBadge::factory()->for($rising)->count(2)->create(['badge' => Badge::Supporter]);

    $this->actingAs($steady)
        ->get(route('leaderboard.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('leaderboard/Index')
            ->where('period', 'week')
            ->has('leaders', 2)
            ->where('leaders.0', [
                'rank' => 1,
                'id' => $rising->id,
                'name' => 'Rising',
                'reputation' => 50,
                'gained' => 40,
                'badges' => ['gold' => 1, 'silver' => 0, 'bronze' => 2],
            ])
            ->where('leaders.1.name', 'Steady'));

    $this->get(route('leaderboard.index', ['period' => 'month']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('period', 'month')->has('leaders', 3));
});

it('ranks everyone by total reputation of all time', function (): void {
    User::factory()->create(['name' => 'Low', 'reputation' => 5]);
    User::factory()->create(['name' => 'High', 'reputation' => 700]);
    User::factory()->unverified()->create(['name' => 'Unverified', 'reputation' => 9000]);

    $this->actingAs(User::factory()->create(['name' => 'Me']))
        ->get(route('leaderboard.index', ['period' => 'all']))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('period', 'all')
            ->where('leaders.0.name', 'High')
            ->where('leaders.0.gained', 0)
            ->where('leaders.1.name', 'Low')
            ->has('leaders', 2));
});

it('validates the period', function (): void {
    $this->actingAs(User::factory()->create())
        ->fromRoute('dashboard')
        ->get(route('leaderboard.index', ['period' => 'decade']))
        ->assertSessionHasErrors('period');
});
