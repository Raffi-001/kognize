<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\Submission;
use App\Models\User;

test('to array', function (): void {
    $report = Report::factory()->create()->refresh();

    expect(array_keys($report->toArray()))
        ->toBe([
            'id',
            'user_id',
            'reportable_type',
            'reportable_id',
            'reason',
            'status',
            'created_at',
            'updated_at',
        ]);
});

it('defaults to pending', function (): void {
    expect(new Report()->status)->toBe(ReportStatus::Pending);
});

it('belongs to a user and a reportable', function (): void {
    $user = User::factory()->create();
    $submission = Submission::factory()->create();
    $report = Report::factory()->for($user)->create(['reportable_id' => $submission->id]);

    expect($report->user->is($user))->toBeTrue()
        ->and($report->reportable)->toBeInstanceOf(Submission::class)
        ->and($report->reportable->is($submission))->toBeTrue();
});
