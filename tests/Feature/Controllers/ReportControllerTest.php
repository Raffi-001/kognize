<?php

declare(strict_types=1);

use App\Models\AnalysisNote;
use App\Models\Comment;
use App\Models\Report;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserNote;

it('requires authentication', function (): void {
    $this->post(route('reports.store'), [])
        ->assertRedirectToRoute('login');
});

it('may report content', function (string $type, Closure $reportable): void {
    $user = User::factory()->create();
    $model = $reportable();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->post(route('reports.store'), [
            'reportable_type' => $type,
            'reportable_id' => $model->getKey(),
            'reason' => 'Spam',
        ]);

    $response->assertRedirectToRoute('dashboard')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Thanks, our team will review this report.')]);

    $report = Report::query()->sole();

    expect($report->user->is($user))->toBeTrue()
        ->and($report->reportable->is($model))->toBeTrue()
        ->and($report->reason)->toBe('Spam');
})->with([
    'submission' => ['submission', fn () => Submission::factory()->create()],
    'analysis note' => ['analysis_note', fn () => AnalysisNote::factory()->create()],
    'user note' => ['user_note', fn () => UserNote::factory()->create()],
    'comment' => ['comment', fn () => Comment::factory()->create()],
]);

it('validates the report', function (array $data, string $error): void {
    $this->actingAs(User::factory()->create())
        ->fromRoute('dashboard')
        ->post(route('reports.store'), $data)
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHasErrors($error);

    expect(Report::query()->count())->toBe(0);
})->with([
    'missing type' => [['reportable_id' => 1, 'reason' => 'Spam'], 'reportable_type'],
    'unknown type' => [['reportable_type' => 'user', 'reportable_id' => 1, 'reason' => 'Spam'], 'reportable_type'],
    'missing id' => [['reportable_type' => 'submission', 'reason' => 'Spam'], 'reportable_id'],
    'missing reason' => [['reportable_type' => 'submission', 'reportable_id' => 1], 'reason'],
]);

it('rejects reports for content that does not exist', function (): void {
    $this->actingAs(User::factory()->create())
        ->fromRoute('dashboard')
        ->post(route('reports.store'), [
            'reportable_type' => 'submission',
            'reportable_id' => 999,
            'reason' => 'Spam',
        ])
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHasErrors(['reportable_id' => 'The content you are reporting no longer exists.']);
});
