<?php

declare(strict_types=1);

use App\Enums\QuestionKind;
use App\Filament\Resources\AnalysisNotes\AnalysisNoteResource;
use App\Filament\Resources\Polls\PollResource;
use App\Models\AnalysisNote;
use App\Models\Evidence;
use App\Models\Poll;
use App\Models\User;

it('redirects guests to the admin login', function (): void {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

it('forbids users who may not access the panel', function (Closure $user): void {
    $this->actingAs($user())
        ->get('/admin')
        ->assertForbidden();
})->with([
    'user' => [fn () => User::factory()->create()],
    'unverified admin' => [fn () => User::factory()->admin()->unverified()->create()],
]);

it('shows the admin dashboard with the moderation queues', function (): void {
    Poll::factory()->count(2)->create();
    Evidence::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertOk()
        ->assertSee('Back to Realcognize')
        ->assertSee(route('dashboard'))
        ->assertSee('Polls')
        ->assertSee('Questions');
});

it('counts the polls waiting for approval', function (): void {
    expect(PollResource::getNavigationBadge())->toBeNull();

    Poll::factory()->count(2)->create();
    Poll::factory()->published()->create();

    expect(PollResource::getNavigationBadge())->toBe('2')
        ->and(PollResource::getNavigationBadgeColor())->toBe('warning');
});

it('counts the open factual questions with evidence', function (): void {
    AnalysisNote::factory()->question()->create();

    expect(AnalysisNoteResource::getNavigationBadge())->toBeNull();

    Evidence::factory()->count(2)->create();
    Evidence::factory()->for(AnalysisNote::factory()->question()->state(['status' => 'resolved']), 'question')->create();
    Evidence::factory()->for(AnalysisNote::factory()->question(QuestionKind::Value), 'question')->create();

    expect(AnalysisNoteResource::getNavigationBadge())->toBe('2')
        ->and(AnalysisNoteResource::getNavigationBadgeColor())->toBe('warning');
});
