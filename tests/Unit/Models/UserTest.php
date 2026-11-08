<?php

declare(strict_types=1);

use App\Enums\NotificationKind;
use App\Enums\Plan;
use App\Enums\UrlSubmissionStatus;
use App\Models\Invitation;
use App\Models\Submission;
use App\Models\UrlSubmission;
use App\Models\User;
use Filament\Facades\Filament;

test('to array', function (): void {
    $user = User::factory()->create()->refresh();

    expect(array_keys($user->toArray()))
        ->toBe([
            'id',
            'name',
            'email',
            'email_verified_at',
            'two_factor_confirmed_at',
            'created_at',
            'updated_at',
            'plan',
            'is_admin',
            'referrer_id',
            'muted_notifications',
            'notification_digest',
        ]);
});

it('knows who referred it and whom it referred', function (): void {
    $referrer = User::factory()->create();
    $referees = User::factory()->count(2)->create(['referrer_id' => $referrer->id]);

    expect($referees[0]->referrer->is($referrer))->toBeTrue()
        ->and($referrer->referees->pluck('id')->sort()->values()->all())
        ->toBe($referees->pluck('id')->sort()->values()->all())
        ->and($referrer->referrer)->toBeNull();
});

it('defaults to the standard plan and no admin rights', function (): void {
    $user = new User();

    expect($user->plan)->toBe(Plan::Standard)
        ->and($user->is_admin)->toBeFalse();
});

it('lets only verified admins into the admin panel', function (Closure $user, bool $canAccess): void {
    expect($user()->canAccessPanel(Filament::getPanel('admin')))->toBe($canAccess);
})->with([
    'verified admin' => [fn () => User::factory()->admin()->create(), true],
    'unverified admin' => [fn () => User::factory()->admin()->unverified()->create(), false],
    'user' => [fn () => User::factory()->create(), false],
]);

it('has submissions, invitations and url submissions', function (): void {
    $user = User::factory()->create();
    $submission = Submission::factory()->for($user)->create();
    $invitation = Invitation::factory()->for($user, 'inviter')->create();
    $urlSubmission = UrlSubmission::factory()->for($user)->create();

    expect($user->submissions->sole()->is($submission))->toBeTrue()
        ->and($user->invitations->sole()->is($invitation))->toBeTrue()
        ->and($user->urlSubmissions->sole()->is($urlSubmission))->toBeTrue();
});

it('counts submissions and queued url submissions made today', function (): void {
    $user = User::factory()->create();

    Submission::factory()->for($user)->count(2)->create();
    Submission::factory()->for($user)->create(['created_at' => now()->subDay()]);
    Submission::factory()->create();

    UrlSubmission::factory()->for($user)->create(['status' => UrlSubmissionStatus::Pending]);
    UrlSubmission::factory()->for($user)->create(['status' => UrlSubmissionStatus::Processing]);
    UrlSubmission::factory()->for($user)->create(['status' => UrlSubmissionStatus::Completed]);
    UrlSubmission::factory()->for($user)->create(['status' => UrlSubmissionStatus::Failed]);
    UrlSubmission::factory()->for($user)->create(['created_at' => now()->subDay()]);

    expect($user->submissionsUsedToday())->toBe(4);
});

it('calculates the remaining submissions for today', function (): void {
    $user = User::factory()->create(['plan' => Plan::Standard]);

    expect($user->remainingSubmissionsToday())->toBe(Plan::Standard->dailySubmissions());

    Submission::factory()->for($user)->create();

    expect($user->remainingSubmissionsToday())->toBe(Plan::Standard->dailySubmissions() - 1);

    Submission::factory()->for($user)->count(Plan::Standard->dailySubmissions())->create();

    expect($user->remainingSubmissionsToday())->toBe(0);
});

it('calculates the remaining invitations', function (): void {
    $user = User::factory()->create(['plan' => Plan::Pro]);

    expect($user->remainingInvitations())->toBe(Plan::Pro->invitations());

    Invitation::factory()->for($user, 'inviter')->used()->create();

    expect($user->remainingInvitations())->toBe(Plan::Pro->invitations() - 1);

    Invitation::factory()->for($user, 'inviter')->count(Plan::Pro->invitations())->create();

    expect($user->remainingInvitations())->toBe(0);
});

it('wants every notification it has not muted', function (): void {
    $user = User::factory()->make(['muted_notifications' => ['comment_posted']]);

    expect($user->wantsNotification(NotificationKind::CommentPosted))->toBeFalse()
        ->and($user->wantsNotification(NotificationKind::EvidenceChecked))->toBeTrue()
        ->and(User::factory()->make()->wantsNotification(NotificationKind::CommentPosted))->toBeTrue();
});
