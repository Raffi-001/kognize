<?php

declare(strict_types=1);

use App\Actions\CreateReport;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\Submission;
use App\Models\User;

it('creates a report', function (): void {
    $user = User::factory()->create();
    $submission = Submission::factory()->create();

    $report = resolve(CreateReport::class)->handle($user, 'submission', $submission->id, '  Spam  ');

    expect($report->user->is($user))->toBeTrue()
        ->and($report->reportable->is($submission))->toBeTrue()
        ->and($report->reason)->toBe('Spam')
        ->and($report->refresh()->status)->toBe(ReportStatus::Pending);
});

it('does not create duplicate reports', function (): void {
    $user = User::factory()->create();
    $submission = Submission::factory()->create();

    $first = resolve(CreateReport::class)->handle($user, 'submission', $submission->id, 'Spam');
    $second = resolve(CreateReport::class)->handle($user, 'submission', $submission->id, 'Other reason');

    expect($second->is($first))->toBeTrue()
        ->and($second->reason)->toBe('Spam')
        ->and(Report::query()->count())->toBe(1);
});
